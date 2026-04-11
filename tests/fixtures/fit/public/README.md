# Public FIT Test Fixtures

Public FIT files from open-source repositories, used to test decoder/parser robustness across devices, sports, and edge cases.

## Sources

| Repository | License | Files |
|---|---|---|
| [tormoder/fit](https://github.com/tormoder/fit) (includes Garmin FIT SDK samples) | BSD-3-Clause | 27 |
| [garmin/fit-javascript-sdk](https://github.com/garmin/fit-javascript-sdk) | Garmin FIT SDK License | 3 |
| [msimms/TestFilesForFitnessApps](https://github.com/msimms/TestFilesForFitnessApps) | MIT | 14 |

## activities/ (29 files)

Files that contain valid FIT activity data. Some fail decoding due to unsupported FIT features (compressed timestamps, developer fields).

| File | Source | Sport | Sub | Laps | HR | Power | Notes |
|---|---|---|---|---|---|---|---|
| run_sdk_sample.fit | fitsdk | 1 (run) | 0 | 1 | - | - | Minimal SDK sample (771 bytes) |
| run_garmin_forerunner10.fit | msimms | 1 (run) | 0 | 1 | - | - | Old device, tiny file |
| run_garmin_fenix3_hr.fit | msimms | - | - | - | - | - | DECODE FAIL (unsupported features) |
| run_garmin_fenix6.fit | msimms | 1 (run) | 0 | 1 | 171 | - | Modern Garmin, 27 message types |
| run_garmin_fenix6_vs_coros.fit | msimms | 1 (run) | 0 | 1 | 170 | - | Same run as COROS comparison |
| run_coros_pace2.fit | msimms | 1 (run) | 0 | 1 | 169 | 269 | Non-Garmin device with power |
| run_fenix2_small.fit | tormoder | 1 (run) | 0 | 4 | - | - | Fenix 2, multi-lap |
| track_run_garmin_fenix6.fit | msimms | 1 (run) | 4 (track) | 5 | 161 | - | Track subSport=4 |
| track_run_coros_pace2.fit | msimms | 1 (run) | 0 | 5 | 160 | 265 | COROS track run with power |
| hike_garmin_fr910xt.fit | msimms | 1 (run) | 0 | 3 | - | - | Hike recorded as sport=1 (FR910XT) |
| swim_pool_with_hr.fit | fitsdk | 5 (swim) | 17 (pool) | 38 | 103 | - | Pool swim, 38 laps, HR data |
| swim_garmin_fr910xt.fit | msimms | 5 (swim) | 18 (open) | 8 | - | - | Open water swim |
| swim_ocean_garmin_fenix3.fit | msimms | 5 (swim) | 18 (open) | 1 | 142 | - | Short ocean swim with HR |
| bike_wahoo_elemnt.fit | msimms | - | - | - | - | - | DECODE FAIL (non-Garmin features) |
| bike_garmin_edge810_power.fit | tormoder | 2 (bike) | 0 | 8 | 154 | 276 | Cycling with Vector power meter |
| bike_garmin_edge500.fit | tormoder | 2 (bike) | 0 | 9 | 162 | - | Large file (357KB), 10915 messages |
| bike_zwift_virtual_race.fit | msimms | 2 (bike) | 58 (virtual) | 1 | 162 | 192 | Virtual cycling, subSport=58 |
| bike_indoor_trainer.fit | tormoder | 2 (bike) | 0 | 5 | 148 | 201 | Indoor trainer with power |
| triathlon_garmin_fenix3.fit | msimms | 5 (swim) | 18 | 5 | - | - | Triathlon (first session = swim) |
| multisport_fenix2_large.fit | tormoder | 0 (generic) | 0 | 7 | - | - | Large multisport (1MB), sport=0 |
| activity_with_hrm_plugin.fit | garmin-js | - | - | - | - | - | DECODE FAIL (float field values) |
| activity_with_gear_changes.fit | garmin-js | - | - | - | - | - | Decodes but MISSING SESSION |
| activity_garmin_js_sdk_sample.fit | garmin-js | - | - | - | - | - | DECODE FAIL (unsupported features) |
| activity_compressed_speed_distance.fit | tormoder | - | - | - | - | - | DECODE FAIL (compressed timestamps) |
| activity_antfs_dump.fit | tormoder | - | - | - | - | - | DECODE FAIL (ANT-FS dump format) |
| activity_developer_data.fit | fitsdk | - | - | - | - | - | DECODE FAIL (developer fields) |
| activity_misc_0134902991.fit | tormoder | - | - | - | - | - | DECODE FAIL (unsupported features) |
| activity_misc_2013_02_06.fit | tormoder | 1 (run) | 0 | 5 | 147 | - | Run from 2013 |
| activity_misc_2015_10_13.fit | tormoder | 2 (bike) | 0 | 1 | - | - | Short bike from 2015 |

## non_activity/ (9 files)

Files with non-activity file types. Parser correctly rejects these with "not an activity file".

| File | Source | File Type | Notes |
|---|---|---|---|
| workout_individual_steps.fit | fitsdk | Workout | Individual workout steps |
| workout_repeat_steps.fit | fitsdk | Workout | Repeat step structure |
| workout_repeat_greater_than_step.fit | fitsdk | Workout | Repeat > step index |
| workout_custom_target_values.fit | fitsdk | Workout | Custom target values |
| workout_half_mile_repeats.fit | msimms | Workout | Half mile repeat workout |
| monitoring_file.fit | fitsdk | Monitoring | Activity monitoring data |
| settings.fit | fitsdk | Settings | Device settings |
| weight_scale_multi_user.fit | fitsdk | Weight | Multi-user weight scale |
| weight_scale_single_user.fit | fitsdk | Weight | Single-user weight scale |

## edge_cases/ (6 files)

Files that test error handling and unusual structures.

| File | Source | Behavior | Notes |
|---|---|---|---|
| corrupt_broken.fit | tormoder | DECODE FAIL | Corrupt/broken file (318KB) |
| unterminated_strings.fit | tormoder | DECODE FAIL | Unterminated string fields |
| chained_activity_activity.fit | tormoder | Reads first FIT | Two activities chained |
| chained_activity_settings.fit | tormoder | Reads first FIT | Activity + settings chained |
| chained_corrupt_header.fit | tormoder | Reads first FIT | Activity + corrupt header |
| chained_nodata.fit | tormoder | Reads first FIT | Activity + empty segment |
