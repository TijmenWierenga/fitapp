<?php

declare(strict_types=1);

namespace Tests\Support;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedExerciseTitle;
use App\DataTransferObjects\Fit\ParsedLap;
use App\DataTransferObjects\Fit\ParsedSession;
use App\DataTransferObjects\Fit\ParsedSet;
use Carbon\CarbonImmutable;

class ParsedActivityBuilder
{
    private int $sport = 1;

    private int $subSport = 0;

    private ?CarbonImmutable $startTime = null;

    private ?int $totalElapsedTime = null;

    private ?float $totalDistance = null;

    private ?int $totalCalories = null;

    private ?int $avgHeartRate = null;

    private ?int $maxHeartRate = null;

    private ?int $avgPower = null;

    private ?string $workoutName = null;

    /** @var list<ParsedLap> */
    private array $laps = [];

    /** @var list<ParsedSet> */
    private array $sets = [];

    /** @var list<ParsedExerciseTitle> */
    private array $exerciseTitles = [];

    public function withSession(
        int $sport,
        int $subSport,
        ?int $totalElapsedTime = null,
        ?float $totalDistance = null,
        ?int $totalCalories = null,
        ?int $avgHeartRate = null,
        ?int $maxHeartRate = null,
        ?int $avgPower = null,
        ?string $workoutName = null,
    ): self {
        $this->sport = $sport;
        $this->subSport = $subSport;
        $this->totalElapsedTime = $totalElapsedTime;
        $this->totalDistance = $totalDistance;
        $this->totalCalories = $totalCalories;
        $this->avgHeartRate = $avgHeartRate;
        $this->maxHeartRate = $maxHeartRate;
        $this->avgPower = $avgPower;
        $this->workoutName = $workoutName;

        return $this;
    }

    public function withStartTime(CarbonImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function addLap(
        int $totalElapsedTime,
        ?float $totalDistance = null,
        ?int $avgHeartRate = null,
        ?int $maxHeartRate = null,
        ?int $avgSpeed = null,
        ?int $avgPower = null,
        ?int $maxPower = null,
        ?int $avgCadence = null,
        ?int $totalAscent = null,
    ): self {
        $this->laps[] = new ParsedLap(
            index: count($this->laps),
            startTime: $this->startTime ?? CarbonImmutable::now(),
            totalElapsedTime: $totalElapsedTime,
            totalDistance: $totalDistance,
            avgHeartRate: $avgHeartRate,
            maxHeartRate: $maxHeartRate,
            avgSpeed: $avgSpeed,
            avgPower: $avgPower,
            maxPower: $maxPower,
            avgCadence: $avgCadence,
            totalAscent: $totalAscent,
        );

        return $this;
    }

    /**
     * @param  float|null  $weight  Weight in kilograms
     */
    public function addSet(
        int $setType = 1,
        ?int $duration = null,
        ?int $repetitions = null,
        ?float $weight = null,
        ?int $exerciseCategory = null,
        ?int $exerciseName = null,
    ): self {
        $this->sets[] = new ParsedSet(
            index: count($this->sets),
            setType: $setType,
            duration: $duration,
            repetitions: $repetitions,
            weight: $weight,
            exerciseCategory: $exerciseCategory,
            exerciseName: $exerciseName,
        );

        return $this;
    }

    public function addRestSet(?int $duration = null): self
    {
        return $this->addSet(setType: 0, duration: $duration);
    }

    public function addExerciseTitle(
        int $exerciseCategory,
        int $exerciseName,
        string $displayName,
    ): self {
        $this->exerciseTitles[] = new ParsedExerciseTitle(
            exerciseCategory: $exerciseCategory,
            exerciseName: $exerciseName,
            displayName: $displayName,
        );

        return $this;
    }

    public function build(): ParsedActivity
    {
        return new ParsedActivity(
            session: new ParsedSession(
                sport: $this->sport,
                subSport: $this->subSport,
                startTime: $this->startTime ?? CarbonImmutable::now(),
                totalElapsedTime: $this->totalElapsedTime,
                totalDistance: $this->totalDistance,
                totalCalories: $this->totalCalories,
                avgHeartRate: $this->avgHeartRate,
                maxHeartRate: $this->maxHeartRate,
                avgPower: $this->avgPower,
                workoutName: $this->workoutName,
            ),
            laps: $this->laps,
            sets: $this->sets,
            exerciseTitles: $this->exerciseTitles,
        );
    }
}
