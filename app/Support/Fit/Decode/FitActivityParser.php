<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedExerciseTitle;
use App\DataTransferObjects\Fit\ParsedLap;
use App\DataTransferObjects\Fit\ParsedSession;
use App\DataTransferObjects\Fit\ParsedSet;
use App\Exceptions\FitParseException;
use App\Support\Fit\FitExerciseTitleField;
use App\Support\Fit\FitFileIdField;
use App\Support\Fit\FitFileType;
use App\Support\Fit\FitInvalidValue;
use App\Support\Fit\FitLapField;
use App\Support\Fit\FitMessage;
use App\Support\Fit\FitMessageType;
use App\Support\Fit\FitScaleFactor;
use App\Support\Fit\FitSessionField;
use App\Support\Fit\FitSetField;
use Carbon\CarbonImmutable;

class FitActivityParser
{
    public function __construct(private FitDecoder $decoder) {}

    public function parse(string $fitData): ParsedActivity
    {
        $messages = $this->decoder->decode($fitData);

        $this->validateActivityFile($messages);

        $session = $this->parseSession($messages);
        $laps = $this->parseLaps($messages);
        $sets = $this->parseSets($messages);
        $exerciseTitles = $this->parseExerciseTitles($messages);

        return new ParsedActivity($session, $laps, $sets, $exerciseTitles);
    }

    /**
     * @param  list<FitMessage>  $messages
     */
    private function validateActivityFile(array $messages): void
    {
        foreach ($messages as $message) {
            if ($message->globalMessageNumber === FitMessageType::FileId->value) {
                $type = $this->getFieldValue($message, FitFileIdField::Type->value);

                if ($type !== FitFileType::Activity->value) {
                    throw FitParseException::notAnActivity();
                }

                return;
            }
        }

        throw FitParseException::notAnActivity();
    }

    /**
     * @param  list<FitMessage>  $messages
     */
    private function parseSession(array $messages): ParsedSession
    {
        foreach ($messages as $message) {
            if ($message->globalMessageNumber !== FitMessageType::Session->value) {
                continue;
            }

            $startTimestamp = $this->getFieldValue($message, FitSessionField::Timestamp->value);
            $startTime = $startTimestamp !== null
                ? CarbonImmutable::createFromTimestamp($startTimestamp + FitScaleFactor::FIT_EPOCH_OFFSET)
                : CarbonImmutable::now();

            $totalElapsedTime = $this->getFieldValue($message, FitSessionField::TotalElapsedTime->value);
            $totalDistance = $this->getFieldValue($message, FitSessionField::TotalDistance->value);

            return new ParsedSession(
                sport: $this->getFieldValue($message, FitSessionField::Sport->value) ?? 0,
                subSport: $this->getFieldValue($message, FitSessionField::SubSport->value) ?? 0,
                startTime: $startTime,
                totalElapsedTime: $totalElapsedTime !== null ? (int) round($totalElapsedTime / FitScaleFactor::MILLISECONDS) : null,
                totalDistance: $totalDistance !== null ? round($totalDistance / FitScaleFactor::CENTIMETERS, 2) : null,
                totalCalories: $this->getFieldValue($message, FitSessionField::TotalCalories->value),
                avgHeartRate: $this->getFieldValue($message, FitSessionField::AvgHeartRate->value),
                maxHeartRate: $this->getFieldValue($message, FitSessionField::MaxHeartRate->value),
                avgPower: $this->getFieldValue($message, FitSessionField::AvgPower->value),
                workoutName: $this->getStringFieldValue($message, FitSessionField::WorkoutName->value),
            );
        }

        throw FitParseException::missingSession();
    }

    /**
     * @param  list<FitMessage>  $messages
     * @return list<ParsedLap>
     */
    private function parseLaps(array $messages): array
    {
        $laps = [];
        $index = 0;

        foreach ($messages as $message) {
            if ($message->globalMessageNumber !== FitMessageType::Lap->value) {
                continue;
            }

            $startTimestamp = $this->getFieldValue($message, FitLapField::Timestamp->value);
            $startTime = $startTimestamp !== null
                ? CarbonImmutable::createFromTimestamp($startTimestamp + FitScaleFactor::FIT_EPOCH_OFFSET)
                : CarbonImmutable::now();

            $totalElapsedTime = $this->getFieldValue($message, FitLapField::TotalElapsedTime->value);
            $totalDistance = $this->getFieldValue($message, FitLapField::TotalDistance->value);
            $avgSpeed = $this->getFieldValue($message, FitLapField::AvgSpeed->value);

            $laps[] = new ParsedLap(
                index: $index,
                startTime: $startTime,
                totalElapsedTime: $totalElapsedTime !== null ? (int) round($totalElapsedTime / FitScaleFactor::MILLISECONDS) : 0,
                totalDistance: $totalDistance !== null ? round($totalDistance / FitScaleFactor::CENTIMETERS, 2) : null,
                avgHeartRate: $this->getFieldValue($message, FitLapField::AvgHeartRate->value),
                maxHeartRate: $this->getFieldValue($message, FitLapField::MaxHeartRate->value),
                avgSpeed: $avgSpeed,
                avgPower: $this->getFieldValue($message, FitLapField::AvgPower->value),
                maxPower: $this->getFieldValue($message, FitLapField::MaxPower->value),
                avgCadence: $this->getFieldValue($message, FitLapField::AvgCadence->value),
                totalAscent: $this->getFieldValue($message, FitLapField::TotalAscent->value),
            );

            $index++;
        }

        return $laps;
    }

    /**
     * @param  list<FitMessage>  $messages
     * @return list<ParsedSet>
     */
    private function parseSets(array $messages): array
    {
        $sets = [];
        $index = 0;

        foreach ($messages as $message) {
            if ($message->globalMessageNumber !== FitMessageType::Set->value) {
                continue;
            }

            $duration = $this->getFieldValue($message, FitSetField::Duration->value);
            $weight = $this->getFieldValue($message, FitSetField::Weight->value);

            // Field 1/2 = legacy exercise_category/exercise_name
            // Field 7/8 = newer category/category_subtype (used by recent Garmin devices)
            $legacyCategory = $this->getFieldValue($message, FitSetField::LegacyCategory->value);
            $legacyName = $this->getFieldValue($message, FitSetField::LegacyName->value);

            $exerciseCategory = ($legacyCategory !== null && $legacyCategory !== FitInvalidValue::UINT16)
                ? $legacyCategory
                : $this->getFieldValue($message, FitSetField::Category->value);

            $exerciseName = ($legacyName !== null && $legacyName !== FitInvalidValue::UINT8 && $legacyName !== FitInvalidValue::UINT16)
                ? $legacyName
                : $this->getFieldValue($message, FitSetField::Name->value);

            $sets[] = new ParsedSet(
                index: $index,
                setType: $this->getFieldValue($message, FitSetField::SetType->value) ?? 0,
                duration: $duration !== null ? (int) round($duration / FitScaleFactor::MILLISECONDS) : null,
                repetitions: $this->getFieldValue($message, FitSetField::Repetitions->value),
                weight: $weight !== null ? round($weight / FitScaleFactor::WEIGHT, 2) : null,
                exerciseCategory: $exerciseCategory,
                exerciseName: $exerciseName,
            );

            $index++;
        }

        return $sets;
    }

    /**
     * @param  list<FitMessage>  $messages
     * @return list<ParsedExerciseTitle>
     */
    private function parseExerciseTitles(array $messages): array
    {
        $titles = [];

        foreach ($messages as $message) {
            if ($message->globalMessageNumber !== FitMessageType::ExerciseTitle->value) {
                continue;
            }

            $exerciseCategory = $this->getFieldValue($message, FitExerciseTitleField::Category->value);
            $exerciseName = $this->getFieldValue($message, FitExerciseTitleField::Name->value);
            $displayName = $this->getStringFieldValue($message, FitExerciseTitleField::DisplayName->value);

            if ($exerciseCategory === null || $exerciseName === null || $displayName === null) {
                continue;
            }

            $titles[] = new ParsedExerciseTitle(
                exerciseCategory: $exerciseCategory,
                exerciseName: $exerciseName,
                displayName: $displayName,
            );
        }

        return $titles;
    }

    private function getFieldValue(FitMessage $message, int $fieldNumber): int|float|null
    {
        foreach ($message->fields as $field) {
            if ($field->fieldNumber === $fieldNumber) {
                $value = $field->value;

                return is_string($value) ? null : $value;
            }
        }

        return null;
    }

    private function getStringFieldValue(FitMessage $message, int $fieldNumber): ?string
    {
        foreach ($message->fields as $field) {
            if ($field->fieldNumber === $fieldNumber) {
                if (! is_string($field->value)) {
                    return null;
                }

                // FIT strings are null-terminated; strip trailing garbage bytes
                $nullPos = strpos($field->value, "\x00");

                return $nullPos !== false ? substr($field->value, 0, $nullPos) : $field->value;
            }
        }

        return null;
    }
}
