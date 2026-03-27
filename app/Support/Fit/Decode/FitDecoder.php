<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\Exceptions\FitParseException;
use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\FitField;
use App\Support\Fit\FitMessage;

class FitDecoder
{
    private const int HEADER_SIZE_MINIMUM = 12;

    private const int HEADER_SIZE_WITH_CRC = 14;

    /**
     * @return list<FitMessage>
     */
    public function decode(string $binary): array
    {
        $length = strlen($binary);

        if ($length < self::HEADER_SIZE_MINIMUM) {
            throw FitParseException::invalidHeader();
        }

        $headerSize = ord($binary[0]);

        if ($headerSize < self::HEADER_SIZE_MINIMUM) {
            throw FitParseException::invalidHeader();
        }

        $dataSize = unpack('V', substr($binary, 4, 4))[1];
        $signature = substr($binary, 8, 4);

        if ($signature !== '.FIT') {
            throw FitParseException::invalidSignature();
        }

        if ($headerSize === self::HEADER_SIZE_WITH_CRC) {
            $headerCrc = unpack('v', substr($binary, 12, 2))[1];
            $calculatedHeaderCrc = FitEncoder::crc16(substr($binary, 0, 12));

            if ($headerCrc !== 0 && $headerCrc !== $calculatedHeaderCrc) {
                throw FitParseException::invalidCrc();
            }
        }

        $expectedLength = $headerSize + $dataSize + 2;

        if ($length < $expectedLength) {
            throw FitParseException::truncatedFile();
        }

        $fileCrc = unpack('v', substr($binary, $headerSize + $dataSize, 2))[1];
        $calculatedFileCrc = FitEncoder::crc16(substr($binary, 0, $headerSize + $dataSize));

        if ($fileCrc !== $calculatedFileCrc) {
            throw FitParseException::invalidCrc();
        }

        return $this->decodeMessages($binary, $headerSize, $headerSize + $dataSize);
    }

    /**
     * @return list<FitMessage>
     */
    private function decodeMessages(string $binary, int $offset, int $endOffset): array
    {
        /** @var array<int, FitMessageDefinition> $definitions */
        $definitions = [];
        $messages = [];

        while ($offset < $endOffset) {
            if ($offset >= strlen($binary)) {
                throw FitParseException::truncatedFile();
            }

            $recordHeader = ord($binary[$offset]);
            $offset++;

            $isDefinition = ($recordHeader & 0x40) !== 0;
            $localMessageType = $recordHeader & 0x0F;

            if ($isDefinition) {
                [$definition, $offset] = $this->parseDefinition($binary, $offset, $localMessageType);
                $definitions[$localMessageType] = $definition;
            } else {
                if (! isset($definitions[$localMessageType])) {
                    throw FitParseException::truncatedFile();
                }

                [$message, $offset] = $this->parseDataMessage($binary, $offset, $definitions[$localMessageType], $localMessageType);
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * @return array{FitMessageDefinition, int}
     */
    private function parseDefinition(string $binary, int $offset, int $localMessageType): array
    {
        if ($offset + 5 > strlen($binary)) {
            throw FitParseException::truncatedFile();
        }

        $offset++; // reserved byte
        $architecture = ord($binary[$offset]);
        $bigEndian = $architecture === 1;
        $offset++;

        $globalMessageNumber = $bigEndian
            ? unpack('n', substr($binary, $offset, 2))[1]
            : unpack('v', substr($binary, $offset, 2))[1];
        $offset += 2;

        $fieldCount = ord($binary[$offset]);
        $offset++;

        if ($offset + ($fieldCount * 3) > strlen($binary)) {
            throw FitParseException::truncatedFile();
        }

        $fieldDefinitions = [];

        for ($i = 0; $i < $fieldCount; $i++) {
            $fieldNumber = ord($binary[$offset]);
            $fieldSize = ord($binary[$offset + 1]);
            $baseTypeValue = ord($binary[$offset + 2]);
            $offset += 3;

            $baseType = FitBaseType::tryFrom($baseTypeValue) ?? FitBaseType::UInt8;

            $fieldDefinitions[] = new FitFieldDefinition($fieldNumber, $fieldSize, $baseType);
        }

        return [
            new FitMessageDefinition($globalMessageNumber, $bigEndian, $fieldDefinitions),
            $offset,
        ];
    }

    /**
     * @return array{FitMessage, int}
     */
    private function parseDataMessage(
        string $binary,
        int $offset,
        FitMessageDefinition $definition,
        int $localMessageType,
    ): array {
        $fields = [];

        foreach ($definition->fieldDefinitions as $fieldDef) {
            if ($offset + $fieldDef->size > strlen($binary)) {
                throw FitParseException::truncatedFile();
            }

            $bytes = substr($binary, $offset, $fieldDef->size);
            $offset += $fieldDef->size;

            if ($fieldDef->baseType === FitBaseType::String) {
                $fields[] = FitField::decode($fieldDef->fieldNumber, $fieldDef->baseType, $bytes, $definition->bigEndian);
            } elseif ($fieldDef->size === $fieldDef->baseType->size()) {
                $fields[] = FitField::decode($fieldDef->fieldNumber, $fieldDef->baseType, $bytes, $definition->bigEndian);
            } elseif ($fieldDef->size > $fieldDef->baseType->size()
                && $fieldDef->size % $fieldDef->baseType->size() === 0) {
                // Array field: decode the first element
                $firstElementBytes = substr($bytes, 0, $fieldDef->baseType->size());
                $fields[] = FitField::decode($fieldDef->fieldNumber, $fieldDef->baseType, $firstElementBytes, $definition->bigEndian);
            } else {
                $fields[] = new FitField($fieldDef->fieldNumber, $fieldDef->baseType, null, $fieldDef->size);
            }
        }

        return [
            new FitMessage($localMessageType, $definition->globalMessageNumber, $fields),
            $offset,
        ];
    }
}
