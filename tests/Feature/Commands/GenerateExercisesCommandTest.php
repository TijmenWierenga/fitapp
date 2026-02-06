<?php

use App\Models\Exercise;
use Illuminate\Support\Facades\Http;

it('skips exercises that already exist', function () {
    Exercise::factory()->create(['name' => 'Zercher Squat']);

    $this->artisan('exercises:generate', ['name' => 'Zercher Squat'])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();
});

it('fails when no name or file is provided', function () {
    $this->artisan('exercises:generate')
        ->expectsOutputToContain('No exercise names provided')
        ->assertFailed();
});

it('shows generated profile in dry-run mode', function () {
    config(['services.anthropic.api_key' => 'test-key']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'name' => 'Zercher Squat',
                        'category' => 'compound',
                        'equipment' => 'barbell',
                        'movement_pattern' => 'squat',
                        'primary_muscles' => ['quadriceps', 'glutes'],
                        'secondary_muscles' => ['core', 'upper_back'],
                        'muscle_loads' => [
                            ['muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.95],
                            ['muscle_group' => 'glutes', 'role' => 'primary', 'load_factor' => 0.8],
                            ['muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.6],
                        ],
                    ]),
                ],
            ],
        ]),
    ]);

    $this->artisan('exercises:generate', ['name' => 'Zercher Squat', '--dry-run' => true])
        ->expectsOutputToContain('Zercher Squat')
        ->assertSuccessful();

    expect(Exercise::query()->where('name', 'Zercher Squat')->exists())->toBeFalse();
});

it('saves exercise when review is confirmed', function () {
    config(['services.anthropic.api_key' => 'test-key']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode([
                        'name' => 'Zercher Squat',
                        'category' => 'compound',
                        'equipment' => 'barbell',
                        'movement_pattern' => 'squat',
                        'primary_muscles' => ['quadriceps', 'glutes'],
                        'secondary_muscles' => ['core'],
                        'muscle_loads' => [
                            ['muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.95],
                            ['muscle_group' => 'glutes', 'role' => 'primary', 'load_factor' => 0.8],
                        ],
                    ]),
                ],
            ],
        ]),
    ]);

    $this->artisan('exercises:generate', ['name' => 'Zercher Squat', '--review' => true])
        ->expectsConfirmation('Save this exercise?', 'yes')
        ->expectsOutputToContain('Saved')
        ->assertSuccessful();

    expect(Exercise::query()->where('name', 'Zercher Squat')->exists())->toBeTrue();
    expect(Exercise::query()->where('name', 'Zercher Squat')->first()->muscleLoads)->toHaveCount(2);
});

it('reads exercise names from file', function () {
    $file = tempnam(sys_get_temp_dir(), 'exercises_');
    file_put_contents($file, "Zercher Squat\nJefferson Squat\n");

    // Both will be skipped since no API key is set and --dry-run won't call API
    Exercise::factory()->create(['name' => 'Zercher Squat']);
    Exercise::factory()->create(['name' => 'Jefferson Squat']);

    $this->artisan('exercises:generate', ['--from-file' => $file])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    unlink($file);
});
