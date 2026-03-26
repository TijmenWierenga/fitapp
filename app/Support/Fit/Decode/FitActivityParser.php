<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedExerciseTitle;
use App\DataTransferObjects\Fit\ParsedLap;
use App\DataTransferObjects\Fit\ParsedSession;
use App\DataTransferObjects\Fit\ParsedSet;
use App\Exceptions\FitParseException;
use App\Support\Fit\FitMessage;
use Carbon\CarbonImmutable;

class FitActivityParser
{
    private const int FIT_EPOCH_OFFSET = 631065600;

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
            if ($message->globalMessageNumber === 0) {
                $type = $this->getFieldValue($message, 0);

                if ($type !== 4) {
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
            if ($message->globalMessageNumber !== 18) {
                continue;
            }

            $startTimestamp = $this->getFieldValue($message, 253);
            $startTime = $startTimestamp !== null
                ? CarbonImmutable::createFromTimestamp($startTimestamp + self::FIT_EPOCH_OFFSET)
                : CarbonImmutable::now();

            $totalElapsedTime = $this->getFieldValue($message, 7);
            $totalDistance = $this->getFieldValue($message, 9);

            return new ParsedSession(
                sport: $this->getFieldValue($message, 5) ?? 0,
                subSport: $this->getFieldValue($message, 6) ?? 0,
                startTime: $startTime,
                totalElapsedTime: $totalElapsedTime !== null ? (int) round($totalElapsedTime / 1000) : null,
                totalDistance: $totalDistance !== null ? round($totalDistance / 100, 2) : null,
                totalCalories: $this->getFieldValue($message, 11),
                avgHeartRate: $this->getFieldValue($message, 16),
                maxHeartRate: $this->getFieldValue($message, 17),
                avgPower: $this->getFieldValue($message, 20),
                workoutName: $this->getStringFieldValue($message, 29),
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
            if ($message->globalMessageNumber !== 19) {
                continue;
            }

            $startTimestamp = $this->getFieldValue($message, 253);
            $startTime = $startTimestamp !== null
                ? CarbonImmutable::createFromTimestamp($startTimestamp + self::FIT_EPOCH_OFFSET)
                : CarbonImmutable::now();

            $totalElapsedTime = $this->getFieldValue($message, 7);
            $totalDistance = $this->getFieldValue($message, 9);
            $avgSpeed = $this->getFieldValue($message, 13);

            $laps[] = new ParsedLap(
                index: $index,
                startTime: $startTime,
                totalElapsedTime: $totalElapsedTime !== null ? (int) round($totalElapsedTime / 1000) : 0,
                totalDistance: $totalDistance !== null ? round($totalDistance / 100, 2) : null,
                avgHeartRate: $this->getFieldValue($message, 15),
                maxHeartRate: $this->getFieldValue($message, 16),
                avgSpeed: $avgSpeed,
                avgPower: $this->getFieldValue($message, 19),
                maxPower: $this->getFieldValue($message, 20),
                avgCadence: $this->getFieldValue($message, 17),
                totalAscent: $this->getFieldValue($message, 21),
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
            if ($message->globalMessageNumber !== 225) {
                continue;
            }

            $duration = $this->getFieldValue($message, 0);
            $weight = $this->getFieldValue($message, 4);

            $sets[] = new ParsedSet(
                index: $index,
                setType: $this->getFieldValue($message, 5) ?? 0,
                duration: $duration !== null ? (int) round($duration / 1000) : null,
                repetitions: $this->getFieldValue($message, 3),
                weight: $weight !== null ? round($weight / 16, 2) : null,
                exerciseCategory: $this->getFieldValue($message, 1),
                exerciseName: $this->getFieldValue($message, 2),
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
            if ($message->globalMessageNumber !== 264) {
                continue;
            }

            $exerciseCategory = $this->getFieldValue($message, 0);
            $exerciseName = $this->getFieldValue($message, 1);
            $displayName = $this->getStringFieldValue($message, 2);

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
                return is_string($field->value) ? $field->value : null;
            }
        }

        return null;
    }
}
