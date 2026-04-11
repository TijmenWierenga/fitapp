<?php

use App\Support\Fit\Decode\FitDecoder;
use App\Support\Fit\FitMessage;

function publicFixturePath(string $subdir, string $filename): string
{
    return dirname(__DIR__, 4)."/fixtures/fit/public/{$subdir}/{$filename}";
}

describe('decodes valid FIT files', function () {
    $decoder = new FitDecoder;

    dataset('decodable activities', [
        'run_sdk_sample' => ['run_sdk_sample.fit'],
        'run_garmin_forerunner10' => ['run_garmin_forerunner10.fit'],
        'run_garmin_fenix6' => ['run_garmin_fenix6.fit'],
        'run_garmin_fenix6_vs_coros' => ['run_garmin_fenix6_vs_coros.fit'],
        'run_coros_pace2' => ['run_coros_pace2.fit'],
        'run_fenix2_small' => ['run_fenix2_small.fit'],
        'track_run_garmin_fenix6' => ['track_run_garmin_fenix6.fit'],
        'track_run_coros_pace2' => ['track_run_coros_pace2.fit'],
        'hike_garmin_fr910xt' => ['hike_garmin_fr910xt.fit'],
        'swim_pool_with_hr' => ['swim_pool_with_hr.fit'],
        'swim_garmin_fr910xt' => ['swim_garmin_fr910xt.fit'],
        'swim_ocean_garmin_fenix3' => ['swim_ocean_garmin_fenix3.fit'],
        'bike_garmin_edge810_power' => ['bike_garmin_edge810_power.fit'],
        'bike_garmin_edge500' => ['bike_garmin_edge500.fit'],
        'bike_zwift_virtual_race' => ['bike_zwift_virtual_race.fit'],
        'bike_indoor_trainer' => ['bike_indoor_trainer.fit'],
        'triathlon_garmin_fenix3' => ['triathlon_garmin_fenix3.fit'],
        'multisport_fenix2_large' => ['multisport_fenix2_large.fit'],
        'activity_with_gear_changes' => ['activity_with_gear_changes.fit'],
        'activity_misc_2013_02_06' => ['activity_misc_2013_02_06.fit'],
        'activity_misc_2015_10_13' => ['activity_misc_2015_10_13.fit'],
    ]);

    it('decodes {0} without error', function (string $file) use ($decoder) {
        $data = file_get_contents(publicFixturePath('activities', $file));
        $messages = $decoder->decode($data);

        expect($messages)->toBeArray()
            ->and($messages)->not->toBeEmpty()
            ->and($messages[0])->toBeInstanceOf(FitMessage::class);

        // Every decodable file should have a FileId message (global 0)
        $hasFileId = collect($messages)->contains(fn (FitMessage $m) => $m->globalMessageNumber === 0);
        expect($hasFileId)->toBeTrue();
    })->with('decodable activities');

    dataset('decodable non-activity files', [
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

    it('decodes non-activity {0} without error', function (string $file) use ($decoder) {
        $data = file_get_contents(publicFixturePath('non_activity', $file));
        $messages = $decoder->decode($data);

        expect($messages)->toBeArray()
            ->and($messages)->not->toBeEmpty();
    })->with('decodable non-activity files');

    dataset('decodable chained files', [
        'chained_activity_activity' => ['chained_activity_activity.fit'],
        'chained_activity_settings' => ['chained_activity_settings.fit'],
        'chained_corrupt_header' => ['chained_corrupt_header.fit'],
        'chained_nodata' => ['chained_nodata.fit'],
    ]);

    it('decodes chained file {0} (reads first FIT in chain)', function (string $file) use ($decoder) {
        $data = file_get_contents(publicFixturePath('edge_cases', $file));
        $messages = $decoder->decode($data);

        expect($messages)->toBeArray()
            ->and($messages)->not->toBeEmpty();
    })->with('decodable chained files');
});

describe('rejects corrupt or unsupported FIT files', function () {
    $decoder = new FitDecoder;

    dataset('undecodable files', [
        'corrupt_broken' => ['edge_cases', 'corrupt_broken.fit'],
        'unterminated_strings' => ['edge_cases', 'unterminated_strings.fit'],
        'activity_antfs_dump' => ['activities', 'activity_antfs_dump.fit'],
        'activity_compressed_speed_distance' => ['activities', 'activity_compressed_speed_distance.fit'],
        'activity_developer_data' => ['activities', 'activity_developer_data.fit'],
        'activity_garmin_js_sdk_sample' => ['activities', 'activity_garmin_js_sdk_sample.fit'],
        'activity_misc_0134902991' => ['activities', 'activity_misc_0134902991.fit'],
        'run_garmin_fenix3_hr' => ['activities', 'run_garmin_fenix3_hr.fit'],
        'bike_wahoo_elemnt' => ['activities', 'bike_wahoo_elemnt.fit'],
    ]);

    it('throws FitParseException for {0}', function (string $dir, string $file) use ($decoder) {
        $data = file_get_contents(publicFixturePath($dir, $file));

        expect(fn () => $decoder->decode($data))
            ->toThrow(\App\Exceptions\FitParseException::class);
    })->with('undecodable files');
});
