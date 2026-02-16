<?php

declare(strict_types=1);

namespace App\Support\Fit;

class FitMessage
{
    /**
     * @param  list<FitField>  $fields
     */
    public function __construct(
        public readonly int $localMessageType,
        public readonly int $globalMessageNumber,
        public readonly array $fields,
    ) {}

    public function encodeDefinition(): string
    {
        $header = pack('C', 0x40 | $this->localMessageType);
        $reserved = pack('C', 0);
        $architecture = pack('C', 0); // little-endian
        $globalMsgNum = pack('v', $this->globalMessageNumber);
        $numFields = pack('C', count($this->fields));

        $fieldDefs = '';
        foreach ($this->fields as $field) {
            $fieldDefs .= pack('CCC', $field->fieldNumber, $field->fieldSize(), $field->baseType->value);
        }

        return $header.$reserved.$architecture.$globalMsgNum.$numFields.$fieldDefs;
    }

    public function encodeData(): string
    {
        $header = pack('C', $this->localMessageType);

        $data = '';
        foreach ($this->fields as $field) {
            $data .= $field->encode();
        }

        return $header.$data;
    }
}
