<?php

use App\DataTransferObjects\Fit\ParsedActivity;
use App\Exceptions\FitParseException;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Fit\Decode\FitDecoder;
use Carbon\CarbonImmutable;

function parsePublicFixture(string $subdir, string $filename): ParsedActivity
{
    $path = dirname(__DIR__, 4)."/fixtures/fit/public/{$subdir}/{$filename}";
    $parser = new FitActivityParser(new FitDecoder);

    return $parser->parse(file_get_contents($path));
}

describe('parses activity files', function () {
    dataset('parseable activities', [
        'run_sdk_sample' => ['run_sdk_sample.fit', 1, 0],
        'run_garmin_forerunner10' => ['run_garmin_forerunner10.fit', 1, 0],
        'run_garmin_fenix6' => ['run_garmin_fenix6.fit', 1, 0],
        'run_garmin_fenix6_vs_coros' => ['run_garmin_fenix6_vs_coros.fit', 1, 0],
        'run_coros_pace2' => ['run_coros_pace2.fit', 1, 0],
        'run_fenix2_small' => ['run_fenix2_small.fit', 1, 0],
        'track_run_garmin_fenix6' => ['track_run_garmin_fenix6.fit', 1, 4],
        'track_run_coros_pace2' => ['track_run_coros_pace2.fit', 1, 0],
        'hike_garmin_fr910xt' => ['hike_garmin_fr910xt.fit', 1, 0],
        'swim_pool_with_hr' => ['swim_pool_with_hr.fit', 5, 17],
        'swim_garmin_fr910xt' => ['swim_garmin_fr910xt.fit', 5, 18],
        'swim_ocean_garmin_fenix3' => ['swim_ocean_garmin_fenix3.fit', 5, 18],
        'bike_garmin_edge810_power' => ['bike_garmin_edge810_power.fit', 2, 0],
        'bike_garmin_edge500' => ['bike_garmin_edge500.fit', 2, 0],
        'bike_zwift_virtual_race' => ['bike_zwift_virtual_race.fit', 2, 58],
        'bike_indoor_trainer' => ['bike_indoor_trainer.fit', 2, 0],
        'triathlon_garmin_fenix3' => ['triathlon_garmin_fenix3.fit', 5, 18],
        'multisport_fenix2_large' => ['multisport_fenix2_large.fit', 0, 0],
        'activity_misc_2013_02_06' => ['activity_misc_2013_02_06.fit', 1, 0],
        'activity_misc_2015_10_13' => ['activity_misc_2015_10_13.fit', 2, 0],
    ]);

    it('parses {0} with correct sport', function (string $file, int $expectedSport, int $expectedSubSport) {
        $activity = parsePublicFixture('activities', $file);

        expect($activity)->toBeInstanceOf(ParsedActivity::class)
            ->and($activity->session->sport)->toBe($expectedSport)
            ->and($activity->session->subSport)->toBe($expectedSubSport)
            ->and($activity->session->startTime)->toBeInstanceOf(CarbonImmutable::class)
            ->and($activity->laps)->toBeArray()
            ->and($activity->sets)->toBeArray()
            ->and($activity->exerciseTitles)->toBeArray();
    })->with('parseable activities');
});

describe('rejects non-activity files', function () {
    dataset('non-activity files', [
        'workout_individual_steps' => ['workout_individual_steps.fit'],
        'workout_repeat_steps' => ['workout_repeat_steps.fit'],
        'workout_repeat_greater_than_step' => ['workout_repeat_greater_than_step.fit'],
        'workout_custom_target_values' => ['workout_custom_target_values.fit'],
        'workout_half_mile_repeats' => ['workout_half_mile_repeats.fit'],
        'monitoring_file' => ['monitoring_file.fit'],
        'settings' => ['settings.fit'],
        'weight_scale_multi_user' => ['weight_scale_multi_user.fit'],
        'weight_scale_single_user' => ['weight_scale_single_user.fit'],
    ]);

    it('throws notAnActivity for {0}', function (string $file) {
        expect(fn () => parsePublicFixture('non_activity', $file))
            ->toThrow(FitParseException::class, 'not an activity file');
    })->with('non-activity files');
});

describe('rejects activity files with missing session', function () {
    it('throws missingSession for activity_with_gear_changes', function () {
        expect(fn () => parsePublicFixture('activities', 'activity_with_gear_changes.fit'))
            ->toThrow(FitParseException::class, 'no session data');
    });
});

describe('detailed assertions on high-value files', function () {
    it('parses pool swim with HR and many laps', function () {
        $activity = parsePublicFixture('activities', 'swim_pool_with_hr.fit');

        expect($activity->session->sport)->toBe(5) // Swimming
            ->and($activity->session->subSport)->toBe(17) // Pool swim (lap swimming)
            ->and($activity->laps)->not->toBeEmpty()
            ->and(count($activity->laps))->toBeGreaterThan(10) // 38 laps
            ->and($activity->session->avgHeartRate)->toBe(103);
    });

    it('parses open water swim', function () {
        $activity = parsePublicFixture('activities', 'swim_ocean_garmin_fenix3.fit');

        expect($activity->session->sport)->toBe(5)
            ->and($activity->session->subSport)->toBe(18) // Open water
            ->and($activity->session->avgHeartRate)->toBe(142);
    });

    it('parses cycling with power meter data', function () {
        $activity = parsePublicFixture('activities', 'bike_garmin_edge810_power.fit');

        expect($activity->session->sport)->toBe(2) // Cycling
            ->and($activity->laps)->not->toBeEmpty()
            ->and(count($activity->laps))->toBe(8)
            ->and($activity->session->avgHeartRate)->toBe(154)
            ->and($activity->session->avgPower)->toBe(276);
    });

    it('parses virtual cycling from Zwift', function () {
        $activity = parsePublicFixture('activities', 'bike_zwift_virtual_race.fit');

        expect($activity->session->sport)->toBe(2) // Cycling
            ->and($activity->session->subSport)->toBe(58) // Virtual activity
            ->and($activity->session->avgHeartRate)->toBe(162)
            ->and($activity->session->avgPower)->toBe(192);
    });

    it('parses indoor trainer with power', function () {
        $activity = parsePublicFixture('activities', 'bike_indoor_trainer.fit');

        expect($activity->session->sport)->toBe(2)
            ->and($activity->session->avgHeartRate)->toBe(148)
            ->and($activity->session->avgPower)->toBe(201);
    });

    it('parses run from COROS device', function () {
        $activity = parsePublicFixture('activities', 'run_coros_pace2.fit');

        expect($activity->session->sport)->toBe(1) // Running
            ->and($activity->session->avgHeartRate)->toBe(169)
            ->and($activity->session->avgPower)->toBe(269);
    });

    it('parses track run with subSport=4', function () {
        $activity = parsePublicFixture('activities', 'track_run_garmin_fenix6.fit');

        expect($activity->session->sport)->toBe(1) // Running
            ->and($activity->session->subSport)->toBe(4) // Track
            ->and($activity->laps)->not->toBeEmpty()
            ->and(count($activity->laps))->toBe(5)
            ->and($activity->session->avgHeartRate)->toBe(161);
    });

    it('parses swim from FR910XT', function () {
        $activity = parsePublicFixture('activities', 'swim_garmin_fr910xt.fit');

        expect($activity->session->sport)->toBe(5) // Swimming
            ->and($activity->session->subSport)->toBe(18) // Open water
            ->and(count($activity->laps))->toBe(8);
    });

    it('parses hike as sport=1', function () {
        $activity = parsePublicFixture('activities', 'hike_garmin_fr910xt.fit');

        // Note: FR910XT records hiking as sport=1 (running), not a dedicated hike sport
        expect($activity->session->sport)->toBe(1)
            ->and(count($activity->laps))->toBe(3);
    });

    it('parses triathlon file (first sport in session)', function () {
        $activity = parsePublicFixture('activities', 'triathlon_garmin_fenix3.fit');

        // Triathlon file - first session is swim
        expect($activity->session->sport)->toBe(5) // Swimming
            ->and($activity->laps)->not->toBeEmpty();
    });

    it('parses multisport with sport=0 (generic)', function () {
        $activity = parsePublicFixture('activities', 'multisport_fenix2_large.fit');

        // Multisport session uses sport=0 (generic)
        expect($activity->session->sport)->toBe(0)
            ->and(count($activity->laps))->toBe(7);
    });
});
