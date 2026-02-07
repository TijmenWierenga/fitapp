<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Workout\Equipment;
use App\Enums\Workout\ExerciseCategory;
use App\Enums\Workout\MovementPattern;
use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use App\Models\Exercise;
use App\Models\ExerciseMuscleLoad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenerateExercisesCommand extends Command
{
    protected $signature = 'exercises:generate
        {name? : Exercise name to generate}
        {--from-file= : Path to a text file with one exercise name per line}
        {--review : Show generated profile and ask for confirmation before saving}
        {--dry-run : Show generated profile without saving}
        {--equipment= : Override equipment type}';

    protected $description = 'Generate exercise profiles using Claude AI';

    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are an exercise science expert. Given an exercise name, return its complete
        muscle activation profile as JSON.

        Rules:
        - Return ONLY valid JSON. No markdown, no explanation, no preamble.
        - Use ONLY these muscle groups: chest, upper_back, shoulders, biceps, triceps,
          forearms, core, lower_back, quadriceps, hamstrings, glutes, calves, hip_flexors,
          cardiovascular
        - Use ONLY these categories: compound, isolation, cardio, mobility
        - Use ONLY these equipment types: barbell, dumbbell, kettlebell, bodyweight,
          machine, cable, band, other
        - Use ONLY these movement patterns: squat, hinge, push, pull, carry, rotation,
          core, other
        - Use ONLY these roles: primary, secondary, stabilizer
        - Load factor guidelines:
          - Primary movers (main target muscles): 0.7–1.0
          - Secondary movers (assist the movement): 0.3–0.6
          - Stabilizers (maintain posture/balance): 0.1–0.3
        - Include ALL meaningfully activated muscles, even stabilizers
        - Be accurate — these values drive recovery and training load calculations
        PROMPT;

    public function handle(): int
    {
        $names = $this->resolveExerciseNames();

        if (empty($names)) {
            $this->error('No exercise names provided. Pass a name argument or use --from-file.');

            return self::FAILURE;
        }

        $apiKey = config('services.anthropic.api_key');

        $created = 0;
        $skipped = 0;

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            if (Exercise::query()->where('name', $name)->exists()) {
                $this->warn("Skipping '{$name}' — already exists in database.");
                $skipped++;

                continue;
            }

            if (! $apiKey) {
                $this->error('ANTHROPIC_API_KEY is not configured. Set it in config/services.php or .env.');

                return self::FAILURE;
            }

            $this->info("Generating profile for: {$name}");

            $profile = $this->generateProfile($name, $apiKey);

            if (! $profile) {
                $this->error("Failed to generate profile for '{$name}'.");

                continue;
            }

            if ($this->option('equipment')) {
                $profile['equipment'] = $this->option('equipment');
            }

            if (! $this->validateProfile($profile)) {
                continue;
            }

            $this->displayProfile($profile);

            if ($this->option('dry-run')) {
                continue;
            }

            if ($this->option('review') && ! $this->confirm('Save this exercise?')) {
                $this->info('Skipped.');

                continue;
            }

            $this->saveProfile($profile);
            $this->info("Saved '{$profile['name']}' successfully.");
            $created++;
        }

        $this->newLine();
        $this->info("Done. Created: {$created}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolveExerciseNames(): array
    {
        if ($this->argument('name')) {
            return [$this->argument('name')];
        }

        if ($file = $this->option('from-file')) {
            if (! file_exists($file)) {
                $this->error("File not found: {$file}");

                return [];
            }

            $contents = file_get_contents($file);

            return $contents !== false
                ? array_filter(explode("\n", $contents), fn (string $line): bool => trim($line) !== '')
                : [];
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generateProfile(string $name, ?string $apiKey): ?array
    {
        if (! $apiKey) {
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 1024,
            'system' => self::SYSTEM_PROMPT,
            'messages' => [
                ['role' => 'user', 'content' => "Generate a muscle activation profile for: {$name}"],
            ],
        ]);

        if (! $response->successful()) {
            $this->error("API request failed: {$response->status()}");

            return null;
        }

        $text = $response->json('content.0.text', '');
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            $this->error("Invalid JSON response: {$text}");

            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function validateProfile(array $profile): bool
    {
        $required = ['name', 'category', 'equipment', 'movement_pattern', 'primary_muscles', 'secondary_muscles', 'muscle_loads'];

        foreach ($required as $field) {
            if (! isset($profile[$field])) {
                $this->error("Missing required field: {$field}");

                return false;
            }
        }

        if (ExerciseCategory::tryFrom($profile['category']) === null) {
            $this->error("Invalid category: {$profile['category']}");

            return false;
        }

        if (Equipment::tryFrom($profile['equipment']) === null) {
            $this->error("Invalid equipment: {$profile['equipment']}");

            return false;
        }

        if (MovementPattern::tryFrom($profile['movement_pattern']) === null) {
            $this->error("Invalid movement pattern: {$profile['movement_pattern']}");

            return false;
        }

        foreach ($profile['muscle_loads'] as $load) {
            if (MuscleGroup::tryFrom($load['muscle_group'] ?? '') === null) {
                $this->warn("Invalid muscle group '{$load['muscle_group']}' — skipping this entry.");
            }

            if (MuscleRole::tryFrom($load['role'] ?? '') === null) {
                $this->warn("Invalid role '{$load['role']}' — skipping this entry.");
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function displayProfile(array $profile): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $profile['name']],
                ['Category', $profile['category']],
                ['Equipment', $profile['equipment']],
                ['Movement', $profile['movement_pattern']],
                ['Primary', implode(', ', $profile['primary_muscles'])],
                ['Secondary', implode(', ', $profile['secondary_muscles'])],
            ]
        );

        $this->table(
            ['Muscle Group', 'Role', 'Load Factor'],
            collect($profile['muscle_loads'])->map(fn (array $load): array => [
                $load['muscle_group'],
                $load['role'],
                (string) $load['load_factor'],
            ])->toArray()
        );
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function saveProfile(array $profile): void
    {
        DB::transaction(function () use ($profile): void {
            $exercise = Exercise::create([
                'name' => $profile['name'],
                'category' => ExerciseCategory::from($profile['category']),
                'equipment' => Equipment::from($profile['equipment']),
                'movement_pattern' => MovementPattern::from($profile['movement_pattern']),
                'primary_muscles' => $profile['primary_muscles'],
                'secondary_muscles' => $profile['secondary_muscles'],
            ]);

            foreach ($profile['muscle_loads'] as $load) {
                $muscleGroup = MuscleGroup::tryFrom($load['muscle_group'] ?? '');
                $role = MuscleRole::tryFrom($load['role'] ?? '');

                if (! $muscleGroup || ! $role) {
                    continue;
                }

                ExerciseMuscleLoad::create([
                    'exercise_id' => $exercise->id,
                    'muscle_group' => $muscleGroup,
                    'role' => $role,
                    'load_factor' => $load['load_factor'],
                ]);
            }
        });
    }
}
