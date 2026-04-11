<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\FitExerciseTitleField;
use App\Support\Fit\FitField;
use App\Support\Fit\FitFileIdField;
use App\Support\Fit\FitFileType;
use App\Support\Fit\FitLapField;
use App\Support\Fit\FitMessage;
use App\Support\Fit\FitMessageType;
use App\Support\Fit\FitScaleFactor;
use App\Support\Fit\FitSessionField;
use App\Support\Fit\FitSetField;

class FitActivityFixtureBuilder
{
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
     * @param  float|null  $weight  Weight in kilograms (encoded as raw * FitScaleFactor::WEIGHT internally)
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
            globalMessageNumber: FitMessageType::FileId->value,
            fields: [
                new FitField(FitFileIdField::Type->value, FitBaseType::Enum, FitFileType::Activity->value),
                new FitField(1, FitBaseType::UInt16, 1), // manufacturer = Garmin
                new FitField(2, FitBaseType::UInt16, 0), // product
                new FitField(3, FitBaseType::UInt32, null), // serial_number
                new FitField(4, FitBaseType::UInt32, $this->startTimestamp - FitScaleFactor::FIT_EPOCH_OFFSET), // time_created
            ],
        );
    }

    private function buildSession(): FitMessage
    {
        $fields = [
            new FitField(FitSessionField::Timestamp->value, FitBaseType::UInt32, $this->startTimestamp - FitScaleFactor::FIT_EPOCH_OFFSET),
            new FitField(FitSessionField::Sport->value, FitBaseType::Enum, $this->sport),
            new FitField(FitSessionField::SubSport->value, FitBaseType::Enum, $this->subSport),
            new FitField(FitSessionField::TotalElapsedTime->value, FitBaseType::UInt32, $this->totalElapsedTime !== null ? $this->totalElapsedTime * FitScaleFactor::MILLISECONDS : null),
            new FitField(FitSessionField::TotalDistance->value, FitBaseType::UInt32, $this->totalDistance !== null ? (int) ($this->totalDistance * FitScaleFactor::CENTIMETERS) : null),
            new FitField(FitSessionField::TotalCalories->value, FitBaseType::UInt16, $this->totalCalories),
            new FitField(FitSessionField::AvgHeartRate->value, FitBaseType::UInt8, $this->avgHeartRate),
            new FitField(FitSessionField::MaxHeartRate->value, FitBaseType::UInt8, $this->maxHeartRate),
            new FitField(FitSessionField::AvgPower->value, FitBaseType::UInt16, $this->avgPower),
        ];

        if ($this->workoutName !== null) {
            $fields[] = new FitField(FitSessionField::WorkoutName->value, FitBaseType::String, $this->workoutName);
        }

        return new FitMessage(
            localMessageType: 1,
            globalMessageNumber: FitMessageType::Session->value,
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
            globalMessageNumber: FitMessageType::Lap->value,
            fields: [
                new FitField(FitLapField::Timestamp->value, FitBaseType::UInt32, $this->startTimestamp - FitScaleFactor::FIT_EPOCH_OFFSET),
                new FitField(FitLapField::TotalElapsedTime->value, FitBaseType::UInt32, $lap['totalElapsedTime'] !== null ? $lap['totalElapsedTime'] * FitScaleFactor::MILLISECONDS : null),
                new FitField(FitLapField::TotalDistance->value, FitBaseType::UInt32, $lap['totalDistance'] !== null ? (int) ($lap['totalDistance'] * FitScaleFactor::CENTIMETERS) : null),
                new FitField(FitLapField::AvgSpeed->value, FitBaseType::UInt16, $lap['avgSpeed']),
                new FitField(FitLapField::AvgHeartRate->value, FitBaseType::UInt8, $lap['avgHeartRate']),
                new FitField(FitLapField::MaxHeartRate->value, FitBaseType::UInt8, $lap['maxHeartRate']),
                new FitField(FitLapField::AvgCadence->value, FitBaseType::UInt8, $lap['avgCadence']),
                new FitField(FitLapField::AvgPower->value, FitBaseType::UInt16, $lap['avgPower']),
                new FitField(FitLapField::MaxPower->value, FitBaseType::UInt16, $lap['maxPower']),
                new FitField(FitLapField::TotalAscent->value, FitBaseType::UInt16, $lap['totalAscent']),
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
            globalMessageNumber: FitMessageType::Set->value,
            fields: [
                new FitField(FitSetField::Duration->value, FitBaseType::UInt32, $set['duration'] !== null ? $set['duration'] * FitScaleFactor::MILLISECONDS : null),
                new FitField(FitSetField::LegacyCategory->value, FitBaseType::UInt16, $set['exerciseCategory']),
                new FitField(FitSetField::LegacyName->value, FitBaseType::UInt16, $set['exerciseName']),
                new FitField(FitSetField::Repetitions->value, FitBaseType::UInt16, $set['repetitions']),
                new FitField(FitSetField::Weight->value, FitBaseType::UInt16, $set['weight'] !== null ? (int) ($set['weight'] * FitScaleFactor::WEIGHT) : null),
                new FitField(FitSetField::SetType->value, FitBaseType::UInt8, $set['setType']),
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
            globalMessageNumber: FitMessageType::ExerciseTitle->value,
            fields: [
                new FitField(254, FitBaseType::UInt16, $index),
                new FitField(FitExerciseTitleField::Category->value, FitBaseType::UInt16, $title['exerciseCategory']),
                new FitField(FitExerciseTitleField::Name->value, FitBaseType::UInt16, $title['exerciseName']),
                new FitField(FitExerciseTitleField::DisplayName->value, FitBaseType::String, $title['displayName']),
            ],
        );
    }
}
