<?php

use App\Exceptions\FitParseException;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Fit\Decode\FitDecoder;

function edgeCasePath(string $filename): string
{
    return dirname(__DIR__, 4)."/fixtures/fit/public/edge_cases/{$filename}";
}

it('rejects corrupt broken file', function () {
    $decoder = new FitDecoder;
    $data = file_get_contents(edgeCasePath('corrupt_broken.fit'));

    expect(fn () => $decoder->decode($data))
        ->toThrow(FitParseException::class);
});

it('rejects file with unterminated strings', function () {
    $decoder = new FitDecoder;
    $data = file_get_contents(edgeCasePath('unterminated_strings.fit'));

    expect(fn () => $decoder->decode($data))
        ->toThrow(FitParseException::class);
});

describe('chained FIT files', function () {
    it('decodes first activity from chained activity+activity file', function () {
        $parser = new FitActivityParser(new FitDecoder);
        $data = file_get_contents(edgeCasePath('chained_activity_activity.fit'));

        $activity = $parser->parse($data);

        // Decoder reads first FIT file in chain, ignores the rest
        expect($activity->session->sport)->toBe(1);
    });

    it('decodes first activity from chained activity+settings file', function () {
        $parser = new FitActivityParser(new FitDecoder);
        $data = file_get_contents(edgeCasePath('chained_activity_settings.fit'));

        $activity = $parser->parse($data);

        expect($activity->session->sport)->toBe(1);
    });

    it('handles chained file with corrupt second header', function () {
        $parser = new FitActivityParser(new FitDecoder);
        $data = file_get_contents(edgeCasePath('chained_corrupt_header.fit'));

        // Decoder reads first FIT file, stops before corrupt second header
        $activity = $parser->parse($data);

        expect($activity->session->sport)->toBe(1);
    });

    it('handles chained file with no-data second segment', function () {
        $parser = new FitActivityParser(new FitDecoder);
        $data = file_get_contents(edgeCasePath('chained_nodata.fit'));

        $activity = $parser->parse($data);

        expect($activity->session->sport)->toBe(1);
    });
});

describe('files with unsupported features', function () {
    it('rejects HRM plugin file due to float field values', function () {
        // This file contains float field values which FitField does not accept
        $decoder = new FitDecoder;
        $data = file_get_contents(dirname(__DIR__, 4).'/fixtures/fit/public/activities/activity_with_hrm_plugin.fit');

        expect(fn () => $decoder->decode($data))->toThrow(\TypeError::class);
    });

    dataset('files using compressed timestamps or developer fields', [
        'activity_antfs_dump' => ['activity_antfs_dump.fit'],
        'activity_compressed_speed_distance' => ['activity_compressed_speed_distance.fit'],
        'activity_developer_data' => ['activity_developer_data.fit'],
        'activity_garmin_js_sdk_sample' => ['activity_garmin_js_sdk_sample.fit'],
        'activity_misc_0134902991' => ['activity_misc_0134902991.fit'],
        'run_garmin_fenix3_hr' => ['run_garmin_fenix3_hr.fit'],
        'bike_wahoo_elemnt' => ['bike_wahoo_elemnt.fit'],
    ]);

    it('throws for {0} (unsupported FIT features)', function (string $file) {
        $decoder = new FitDecoder;
        $data = file_get_contents(dirname(__DIR__, 4)."/fixtures/fit/public/activities/{$file}");

        expect(fn () => $decoder->decode($data))
            ->toThrow(FitParseException::class);
    })->with('files using compressed timestamps or developer fields');
});
