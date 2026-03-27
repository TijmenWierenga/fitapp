<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\FitField;
use App\Support\Fit\FitMessage;

class FitActivityFixtureBuilder
{
    private const int FIT_EPOCH_OFFSET = 631065600;

    private int $sport = 1;

    private int $subSport = 0;

    private int $startTimestamp;

    private ?int $totalElapsedTime = null;

    private ?int $totalDistance = null;

    private ?int $totalCalories = null;

    private ?int $avgHeartRate = null;

    private ?int $maxHeartRate = null;

    private ?int $avgPower = null;

    private ?string $workoutName = null;

    /** @var list<array<string, mixed>> */
    private array $laps = [];

    /** @var list<array<string, mixed>> */
    private array $sets = [];

    /** @var list<array<string, mixed>> */
    private array $exerciseTitles = [];

    public function __construct()
    {
        $this->startTimestamp = time();
    }

    public function withSession(
        int $sport,
        int $subSport,
        ?int $totalElapsedTime = null,
        ?int $totalDistance = null,
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

    public function withStartTimestamp(int $timestamp): self
    {
        $this->startTimestamp = $timestamp;

        return $this;
    }

    public function addLap(
        ?int $totalElapsedTime = null,
        ?int $totalDistance = null,
        ?int $avgHeartRate = null,
        ?int $maxHeartRate = null,
        ?int $avgSpeed = null,
        ?int $avgPower = null,
        ?int $maxPower = null,
        ?int $avgCadence = null,
        ?int $totalAscent = null,
    ): self {
        $this->laps[] = [
            'totalElapsedTime' => $totalElapsedTime,
            'totalDistance' => $totalDistance,
            'avgHeartRate' => $avgHeartRate,
            'maxHeartRate' => $maxHeartRate,
            'avgSpeed' => $avgSpeed,
            'avgPower' => $avgPower,
            'maxPower' => $maxPower,
            'avgCadence' => $avgCadence,
            'totalAscent' => $totalAscent,
        ];

        return $this;
    }

    /**
     * @param  int  $weight  Weight in grams (Garmin uses weight_scale=16, so this is the raw value * 16)
     */
    public function addSet(
        int $setType = 0,
        ?int $duration = null,
        ?int $repetitions = null,
        ?float $weight = null,
        ?int $exerciseCategory = null,
        ?int $exerciseName = null,
    ): self {
        $this->sets[] = [
            'setType' => $setType,
            'duration' => $duration,
            'repetitions' => $repetitions,
            'weight' => $weight,
            'exerciseCategory' => $exerciseCategory,
            'exerciseName' => $exerciseName,
        ];

        return $this;
    }

    public function addExerciseTitle(
        int $exerciseCategory,
        int $exerciseName,
        string $displayName,
    ): self {
        $this->exerciseTitles[] = [
            'exerciseCategory' => $exerciseCategory,
            'exerciseName' => $exerciseName,
            'displayName' => $displayName,
        ];

        return $this;
    }

    public function build(): string
    {
        $messages = [];

        $messages[] = $this->buildFileId();
        $messages[] = $this->buildSession();

        foreach ($this->laps as $lap) {
            $messages[] = $this->buildLap($lap);
        }

        foreach ($this->sets as $set) {
            $messages[] = $this->buildSet($set);
        }

        foreach ($this->exerciseTitles as $index => $title) {
            $messages[] = $this->buildExerciseTitle($index, $title);
        }

        return (new FitEncoder)->encode($messages);
    }

    private function buildFileId(): FitMessage
    {
        return new FitMessage(
            localMessageType: 0,
            globalMessageNumber: 0,
            fields: [
                new FitField(0, FitBaseType::Enum, 4), // type = activity
                new FitField(1, FitBaseType::UInt16, 1), // manufacturer = Garmin
                new FitField(2, FitBaseType::UInt16, 0), // product
                new FitField(3, FitBaseType::UInt32, null), // serial_number
                new FitField(4, FitBaseType::UInt32, $this->startTimestamp - self::FIT_EPOCH_OFFSET), // time_created
            ],
        );
    }

    private function buildSession(): FitMessage
    {
        $fields = [
            new FitField(253, FitBaseType::UInt32, $this->startTimestamp - self::FIT_EPOCH_OFFSET), // timestamp
            new FitField(5, FitBaseType::Enum, $this->sport),
            new FitField(6, FitBaseType::Enum, $this->subSport),
            new FitField(7, FitBaseType::UInt32, $this->totalElapsedTime !== null ? $this->totalElapsedTime * 1000 : null), // ms
            new FitField(9, FitBaseType::UInt32, $this->totalDistance !== null ? (int) ($this->totalDistance * 100) : null), // cm
            new FitField(11, FitBaseType::UInt16, $this->totalCalories),
            new FitField(16, FitBaseType::UInt8, $this->avgHeartRate),
            new FitField(17, FitBaseType::UInt8, $this->maxHeartRate),
            new FitField(20, FitBaseType::UInt16, $this->avgPower),
        ];

        if ($this->workoutName !== null) {
            $fields[] = new FitField(29, FitBaseType::String, $this->workoutName);
        }

        return new FitMessage(
            localMessageType: 1,
            globalMessageNumber: 18,
            fields: $fields,
        );
    }

    /**
     * @param  array<string, mixed>  $lap
     */
    private function buildLap(array $lap): FitMessage
    {
        return new FitMessage(
            localMessageType: 2,
            globalMessageNumber: 19,
            fields: [
                new FitField(253, FitBaseType::UInt32, $this->startTimestamp - self::FIT_EPOCH_OFFSET), // timestamp
                new FitField(7, FitBaseType::UInt32, $lap['totalElapsedTime'] !== null ? $lap['totalElapsedTime'] * 1000 : null),
                new FitField(9, FitBaseType::UInt32, $lap['totalDistance'] !== null ? (int) ($lap['totalDistance'] * 100) : null),
                new FitField(13, FitBaseType::UInt16, $lap['avgSpeed']),
                new FitField(15, FitBaseType::UInt8, $lap['avgHeartRate']),
                new FitField(16, FitBaseType::UInt8, $lap['maxHeartRate']),
                new FitField(17, FitBaseType::UInt8, $lap['avgCadence']),
                new FitField(19, FitBaseType::UInt16, $lap['avgPower']),
                new FitField(20, FitBaseType::UInt16, $lap['maxPower']),
                new FitField(21, FitBaseType::UInt16, $lap['totalAscent']),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $set
     */
    private function buildSet(array $set): FitMessage
    {
        return new FitMessage(
            localMessageType: 3,
            globalMessageNumber: 225,
            fields: [
                new FitField(0, FitBaseType::UInt32, $set['duration'] !== null ? $set['duration'] * 1000 : null), // ms
                new FitField(1, FitBaseType::UInt16, $set['exerciseCategory']),
                new FitField(2, FitBaseType::UInt16, $set['exerciseName']),
                new FitField(3, FitBaseType::UInt16, $set['repetitions']),
                new FitField(4, FitBaseType::UInt16, $set['weight'] !== null ? (int) ($set['weight'] * 16) : null), // weight_scale = 16
                new FitField(5, FitBaseType::UInt8, $set['setType']),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $title
     */
    private function buildExerciseTitle(int $index, array $title): FitMessage
    {
        return new FitMessage(
            localMessageType: 4,
            globalMessageNumber: 264,
            fields: [
                new FitField(254, FitBaseType::UInt16, $index),
                new FitField(0, FitBaseType::UInt16, $title['exerciseCategory']),
                new FitField(1, FitBaseType::UInt16, $title['exerciseName']),
                new FitField(2, FitBaseType::String, $title['displayName']),
            ],
        );
    }
}
