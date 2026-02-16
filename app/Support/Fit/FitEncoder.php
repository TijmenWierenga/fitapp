<?php

declare(strict_types=1);

namespace App\Support\Fit;

class FitEncoder
{
    /**
     * @param  list<FitMessage>  $messages
     */
    public function encode(array $messages): string
    {
        $data = $this->encodeMessages($messages);
        $header = $this->encodeHeader(strlen($data));
        $file = $header.$data;
        $file .= pack('v', self::crc16($file));

        return $file;
    }

    /**
     * @param  list<FitMessage>  $messages
     */
    private function encodeMessages(array $messages): string
    {
        $data = '';
        /** @var array<int, string> $definedTypes */
        $definedTypes = [];

        foreach ($messages as $message) {
            $defSignature = $this->definitionSignature($message);

            if (! isset($definedTypes[$message->localMessageType]) || $definedTypes[$message->localMessageType] !== $defSignature) {
                $data .= $message->encodeDefinition();
                $definedTypes[$message->localMessageType] = $defSignature;
            }

            $data .= $message->encodeData();
        }

        return $data;
    }

    private function definitionSignature(FitMessage $message): string
    {
        $sig = "{$message->globalMessageNumber}:";

        foreach ($message->fields as $field) {
            $sig .= "{$field->fieldNumber},{$field->fieldSize()},{$field->baseType->value};";
        }

        return $sig;
    }

    private function encodeHeader(int $dataSize): string
    {
        $header = pack('C', 14); // header size
        $header .= pack('C', 0x20); // protocol version 2.0
        $header .= pack('v', 2100); // profile version 21.00
        $header .= pack('V', $dataSize);
        $header .= '.FIT';
        $header .= pack('v', self::crc16(substr($header, 0, 12)));

        return $header;
    }

    public static function crc16(string $data): int
    {
        $crc = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $byte = ord($data[$i]);
            $crc ^= $byte;

            for ($bit = 0; $bit < 8; $bit++) {
                if ($crc & 1) {
                    $crc = ($crc >> 1) ^ 0xA001;
                } else {
                    $crc >>= 1;
                }
            }
        }

        return $crc & 0xFFFF;
    }
}
