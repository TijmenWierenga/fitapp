<?php

declare(strict_types=1);

namespace App\Support\Fit;

class FitMessageFactory
{
    private const int FIT_EPOCH_OFFSET = 631065600;

    public static function fileId(): FitMessage
    {
        return new FitMessage(
            localMessageType: 0,
            globalMessageNumber: 0,
            fields: [
                new FitField(0, FitBaseType::Enum, 5), // type = workout
                new FitField(1, FitBaseType::UInt16, 1), // manufacturer = Garmin
                new FitField(2, FitBaseType::UInt16, 0), // product
                new FitField(3, FitBaseType::UInt32, null), // serial_number
                new FitField(4, FitBaseType::UInt32, time() - self::FIT_EPOCH_OFFSET), // time_created
            ],
        );
    }

    public static function workout(string $name, int $sport, int $subSport, int $numSteps): FitMessage
    {
        return new FitMessage(
            localMessageType: 1,
            globalMessageNumber: 26,
            fields: [
                new FitField(8, FitBaseType::String, $name), // wkt_name
                new FitField(4, FitBaseType::Enum, $sport), // sport
                new FitField(11, FitBaseType::Enum, $subSport), // sub_sport (field 11, not 5)
                new FitField(6, FitBaseType::UInt16, $numSteps), // num_valid_steps
            ],
        );
    }

    public static function workoutStep(
        int $messageIndex,
        ?string $stepName,
        int $durationType,
        ?int $durationValue,
        int $targetType,
        ?int $targetValue,
        ?int $customTargetLow,
        ?int $customTargetHigh,
        int $intensity,
        ?string $notes = null,
    ): FitMessage {
        $fields = [
            new FitField(254, FitBaseType::UInt16, $messageIndex),
            new FitField(0, FitBaseType::String, $stepName),
            new FitField(1, FitBaseType::Enum, $durationType),
            new FitField(2, FitBaseType::UInt32, $durationValue),
            new FitField(3, FitBaseType::Enum, $targetType),
            new FitField(4, FitBaseType::UInt32, $targetValue),
            new FitField(5, FitBaseType::UInt32, $customTargetLow),
            new FitField(6, FitBaseType::UInt32, $customTargetHigh),
            new FitField(7, FitBaseType::Enum, $intensity),
        ];

        if ($notes !== null) {
            $fields[] = new FitField(8, FitBaseType::String, $notes);
        }

        return new FitMessage(
            localMessageType: 2,
            globalMessageNumber: 27,
            fields: $fields,
        );
    }
}
