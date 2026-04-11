# Activity.fit (SDK Sample)

Synthetic FIT file generated for testing. Minimal size (261 bytes).

## Session

| Field | Value |
|-------|-------|
| Activity | Run (sport=1, subSport=0) |
| Start | 2026-03-22 11:02:15 |
| Duration | 1800s (30 min) |
| Distance | 5000m (5 km) |
| Calories | 350 |
| Avg HR | 155 bpm |
| Max HR | 178 bpm |
| Power | - |
| Workout Name | - |

## Structure

- **Laps:** 5 (each 360s / 1000m, even splits)
- **Sets:** 0
- **Exercise Titles:** 0

## Lap Details

| Lap | Duration | Distance | Avg HR |
|-----|----------|----------|--------|
| 0 | 360s | 1000m | 150 |
| 1 | 360s | 1000m | 155 |
| 2 | 360s | 1000m | 158 |
| 3 | 360s | 1000m | 160 |
| 4 | 360s | 1000m | 157 |

## Test Value

Simple cardio-only file with uniform laps. Good for testing basic decoder/parser functionality without exercise data complexity. Used in `FitDecoderTest` and `FitActivityParserTest`.
