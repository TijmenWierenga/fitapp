<?php

namespace Database\Seeders;

use App\Enums\BodyPart;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Exercise;
use App\Models\Injury;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkoutSeeder extends Seeder
{
    /**
     * Weekly training templates: each week has a set of workouts.
     * Progressively increase volume/intensity across weeks.
     *
     * @var array<int, array<int, array{name: string, activity: Activity, day: int, hour: int}>>
     */
    private const WEEKLY_TEMPLATE = [
        // Mon=0, Tue=1, Wed=2, Thu=3, Fri=4, Sat=5, Sun=6
        ['name' => 'Upper Body Strength', 'activity' => Activity::Strength, 'day' => 0, 'hour' => 7],
        ['name' => 'Easy Run', 'activity' => Activity::Run, 'day' => 1, 'hour' => 6],
        ['name' => 'Lower Body Strength', 'activity' => Activity::Strength, 'day' => 2, 'hour' => 7],
        ['name' => 'HIIT Session', 'activity' => Activity::HIIT, 'day' => 3, 'hour' => 17],
        ['name' => 'Yoga & Mobility', 'activity' => Activity::Yoga, 'day' => 4, 'hour' => 18],
        ['name' => 'Long Run', 'activity' => Activity::Run, 'day' => 5, 'hour' => 8],
    ];

    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $this->seedPastWorkouts($user);
            $this->seedUpcomingWorkouts($user);
            $this->seedInjury($user);
        });

        $this->command->info('Seeded 8 weeks of realistic workout data.');
    }

    private function seedPastWorkouts(User $user): void
    {
        $now = CarbonImmutable::now();
        $exercises = Exercise::all();

        // Find exercises by name for realistic linking
        $benchPress = $exercises->firstWhere('name', 'Barbell Bench Press - Medium Grip');
        $overheadPress = $exercises->firstWhere('name', 'Standing Military Press');
        $barbellRow = $exercises->firstWhere('name', 'Bent Over Barbell Row');
        $pullUp = $exercises->firstWhere('name', 'Pullups');
        $dbCurl = $exercises->firstWhere('name', 'Dumbbell Bicep Curl');
        $tricepPushdown = $exercises->firstWhere('name', 'Triceps Pushdown');
        $squat = $exercises->firstWhere('name', 'Barbell Squat');
        $deadlift = $exercises->firstWhere('name', 'Barbell Deadlift');
        $legPress = $exercises->firstWhere('name', 'Leg Press');
        $legCurl = $exercises->firstWhere('name', 'Seated Leg Curl');
        $calfRaise = $exercises->firstWhere('name', 'Standing Calf Raises');
        $plank = $exercises->firstWhere('name', 'Plank');

        for ($weekOffset = 7; $weekOffset >= 0; $weekOffset--) {
            $weekStart = $now->startOfWeek()->subWeeks($weekOffset);

            // Progressive overload: increase weights slightly each week
            $weightMultiplier = 1.0 + (7 - $weekOffset) * 0.02; // +2% per week
            $rpeBase = min(6 + (7 - $weekOffset) * 0.3, 9); // RPE climbs from 6 to ~8

            // Skip some sessions in earlier weeks (deload-like start)
            $skipProbability = $weekOffset >= 6 ? 30 : 10;

            foreach (self::WEEKLY_TEMPLATE as $template) {
                if (fake()->numberBetween(1, 100) <= $skipProbability) {
                    continue;
                }

                $scheduledAt = $weekStart->addDays($template['day'])->setHour($template['hour']);

                // Only create completed workouts for past dates
                if ($scheduledAt->isFuture()) {
                    continue;
                }

                $rpe = max(1, min(10, (int) round($rpeBase + fake()->numberBetween(-1, 1))));
                $feeling = fake()->randomElement([3, 3, 4, 4, 4, 5]);

                $workout = Workout::factory()->create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'activity' => $template['activity'],
                    'scheduled_at' => $scheduledAt,
                    'completed_at' => $scheduledAt->addMinutes(fake()->numberBetween(0, 15)),
                    'rpe' => $rpe,
                    'feeling' => $feeling,
                ]);

                match ($template['activity']) {
                    Activity::Strength => $template['name'] === 'Upper Body Strength'
                        ? $this->buildUpperBodyStrength($workout, $weightMultiplier, $benchPress, $overheadPress, $barbellRow, $pullUp, $dbCurl, $tricepPushdown)
                        : $this->buildLowerBodyStrength($workout, $weightMultiplier, $squat, $deadlift, $legPress, $legCurl, $calfRaise, $plank),
                    Activity::Run => $template['name'] === 'Long Run'
                        ? $this->buildLongRun($workout)
                        : $this->buildEasyRun($workout),
                    Activity::HIIT => $this->buildHiit($workout),
                    Activity::Yoga => $this->buildYoga($workout),
                    default => null,
                };
            }
        }
    }

    private function buildUpperBodyStrength(
        Workout $workout,
        float $weightMultiplier,
        ?Exercise $benchPress,
        ?Exercise $overheadPress,
        ?Exercise $barbellRow,
        ?Exercise $pullUp,
        ?Exercise $dbCurl,
        ?Exercise $tricepPushdown,
    ): void {
        // Section 1: Main lifts (straight sets)
        $mainSection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Main Lifts', 'order' => 0,
        ]);

        $block1 = Block::factory()->create([
            'section_id' => $mainSection->id, 'block_type' => BlockType::StraightSets, 'order' => 0,
        ]);
        $this->addStrengthExercise($block1, 'Bench Press', 0, 4, 8, round(70 * $weightMultiplier, 2), $benchPress);
        $this->addStrengthExercise($block1, 'Overhead Press', 1, 3, 10, round(40 * $weightMultiplier, 2), $overheadPress);

        // Section 2: Superset (back + arms)
        $supersetSection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Accessories', 'order' => 1,
        ]);

        $block2 = Block::factory()->superset()->create([
            'section_id' => $supersetSection->id, 'order' => 0,
            'rounds' => 3, 'rest_between_rounds' => 90,
        ]);
        $this->addStrengthExercise($block2, 'Barbell Row', 0, null, 10, round(60 * $weightMultiplier, 2), $barbellRow);
        $this->addStrengthExercise($block2, 'Pull-up', 1, null, 8, null, $pullUp);

        $block3 = Block::factory()->superset()->create([
            'section_id' => $supersetSection->id, 'order' => 1,
            'rounds' => 3, 'rest_between_rounds' => 60,
        ]);
        $this->addStrengthExercise($block3, 'Bicep Curl', 0, null, 12, round(12 * $weightMultiplier, 2), $dbCurl);
        $this->addStrengthExercise($block3, 'Tricep Pushdown', 1, null, 12, round(20 * $weightMultiplier, 2), $tricepPushdown);
    }

    private function buildLowerBodyStrength(
        Workout $workout,
        float $weightMultiplier,
        ?Exercise $squat,
        ?Exercise $deadlift,
        ?Exercise $legPress,
        ?Exercise $legCurl,
        ?Exercise $calfRaise,
        ?Exercise $plank,
    ): void {
        // Section 1: Main lifts
        $mainSection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Main Lifts', 'order' => 0,
        ]);

        $block1 = Block::factory()->create([
            'section_id' => $mainSection->id, 'block_type' => BlockType::StraightSets, 'order' => 0,
        ]);
        $this->addStrengthExercise($block1, 'Barbell Squat', 0, 4, 6, round(100 * $weightMultiplier, 2), $squat);
        $this->addStrengthExercise($block1, 'Deadlift', 1, 3, 5, round(120 * $weightMultiplier, 2), $deadlift);

        // Section 2: Accessories (circuit)
        $accessorySection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Accessories', 'order' => 1,
        ]);

        $block2 = Block::factory()->circuit()->create([
            'section_id' => $accessorySection->id, 'order' => 0,
            'rounds' => 3, 'rest_between_exercises' => 30, 'rest_between_rounds' => 60,
        ]);
        $this->addDurationExercise($block2, 'Leg Press', 0, 45, $legPress);
        $this->addDurationExercise($block2, 'Leg Curl', 1, 30, $legCurl);
        $this->addDurationExercise($block2, 'Calf Raises', 2, 30, $calfRaise);

        // Section 3: Core
        $coreSection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Core', 'order' => 2,
        ]);

        $block3 = Block::factory()->create([
            'section_id' => $coreSection->id, 'block_type' => BlockType::StraightSets, 'order' => 0,
        ]);
        $this->addDurationExercise($block3, 'Plank', 0, 60, $plank);
    }

    private function buildEasyRun(Workout $workout): void
    {
        $section = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Run', 'order' => 0,
        ]);

        $block = Block::factory()->distanceDuration()->create([
            'section_id' => $section->id, 'order' => 0,
        ]);

        // 30-40 min easy run
        $duration = fake()->randomElement([1800, 2100, 2400]);
        $distance = $duration / 60 * fake()->numberBetween(140, 170); // ~8.5-10 km/h pace
        $this->addCardioExercise($block, 'Easy Run', 0, $duration, round($distance, 2), 2);
    }

    private function buildLongRun(Workout $workout): void
    {
        $section = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Run', 'order' => 0,
        ]);

        $block = Block::factory()->distanceDuration()->create([
            'section_id' => $section->id, 'order' => 0,
        ]);

        // 60-90 min long run
        $duration = fake()->randomElement([3600, 4200, 4800, 5400]);
        $distance = $duration / 60 * fake()->numberBetween(140, 160); // slower pace
        $this->addCardioExercise($block, 'Long Run', 0, $duration, round($distance, 2), 2);
    }

    private function buildHiit(Workout $workout): void
    {
        // Warm-up
        $warmup = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Warm-up', 'order' => 0,
        ]);
        $warmupBlock = Block::factory()->distanceDuration()->create([
            'section_id' => $warmup->id, 'order' => 0,
        ]);
        $this->addCardioExercise($warmupBlock, 'Warm-up Jog', 0, 300, 600.00, 1);

        // Main HIIT: intervals
        $main = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Intervals', 'order' => 1,
        ]);
        $intervalBlock = Block::factory()->interval()->create([
            'section_id' => $main->id, 'order' => 0,
            'rounds' => 8, 'work_interval' => 30, 'rest_interval' => 15,
        ]);

        // AMRAP finisher
        $finisher = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Finisher', 'order' => 2,
        ]);
        Block::factory()->amrap()->create([
            'section_id' => $finisher->id, 'order' => 0,
            'time_cap' => 600,
        ]);
    }

    private function buildYoga(Workout $workout): void
    {
        $section = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Flow', 'order' => 0,
        ]);

        // Single duration block for yoga
        $block = Block::factory()->distanceDuration()->create([
            'section_id' => $section->id, 'order' => 0,
        ]);
        $this->addDurationExercise($block, 'Yoga Flow', 0, fake()->randomElement([1800, 2400, 3600]));

        // Rest block
        $restSection = Section::factory()->create([
            'workout_id' => $workout->id, 'name' => 'Savasana', 'order' => 1,
        ]);
        $restBlock = Block::factory()->rest()->create([
            'section_id' => $restSection->id, 'order' => 0,
        ]);
        $this->addDurationExercise($restBlock, 'Savasana', 0, 300);
    }

    private function addStrengthExercise(
        Block $block,
        string $name,
        int $order,
        ?int $sets,
        int $reps,
        ?float $weight,
        ?Exercise $exercise = null,
    ): void {
        $strength = StrengthExercise::factory()->create([
            'target_sets' => $sets,
            'target_reps_min' => $reps,
            'target_reps_max' => $reps,
            'target_weight' => $weight,
            'target_rpe' => fake()->randomElement([7.0, 7.5, 8.0, 8.5]),
            'rest_after' => fake()->randomElement([60, 90, 120]),
        ]);

        BlockExercise::factory()->create([
            'block_id' => $block->id,
            'exercise_id' => $exercise?->id,
            'name' => $name,
            'order' => $order,
            'exerciseable_type' => $strength->getMorphClass(),
            'exerciseable_id' => $strength->id,
        ]);
    }

    private function addCardioExercise(
        Block $block,
        string $name,
        int $order,
        int $duration,
        float $distance,
        int $hrZone,
    ): void {
        $cardio = CardioExercise::factory()->create([
            'target_duration' => $duration,
            'target_distance' => $distance,
            'target_heart_rate_zone' => $hrZone,
        ]);

        BlockExercise::factory()->create([
            'block_id' => $block->id,
            'name' => $name,
            'order' => $order,
            'exerciseable_type' => $cardio->getMorphClass(),
            'exerciseable_id' => $cardio->id,
        ]);
    }

    private function addDurationExercise(
        Block $block,
        string $name,
        int $order,
        int $duration,
        ?Exercise $exercise = null,
    ): void {
        $durationExercise = DurationExercise::factory()->create([
            'target_duration' => $duration,
        ]);

        BlockExercise::factory()->create([
            'block_id' => $block->id,
            'exercise_id' => $exercise?->id,
            'name' => $name,
            'order' => $order,
            'exerciseable_type' => $durationExercise->getMorphClass(),
            'exerciseable_id' => $durationExercise->id,
        ]);
    }

    private function seedUpcomingWorkouts(User $user): void
    {
        $now = CarbonImmutable::now();

        // Next 2 weeks of planned workouts
        for ($weekOffset = 0; $weekOffset <= 1; $weekOffset++) {
            $weekStart = $now->startOfWeek()->addWeeks($weekOffset);

            foreach (self::WEEKLY_TEMPLATE as $template) {
                $scheduledAt = $weekStart->addDays($template['day'])->setHour($template['hour']);

                if ($scheduledAt->isPast()) {
                    continue;
                }

                Workout::factory()->create([
                    'user_id' => $user->id,
                    'name' => $template['name'],
                    'activity' => $template['activity'],
                    'scheduled_at' => $scheduledAt,
                    'completed_at' => null,
                ]);
            }
        }
    }

    private function seedInjury(User $user): void
    {
        Injury::factory()->active()->create([
            'user_id' => $user->id,
            'body_part' => BodyPart::Knee,
            'notes' => 'Mild patellar tendinitis - monitor during squats',
        ]);
    }
}
