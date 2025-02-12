<?php

namespace Twee\Cache\Storage\Adapter\Packer;

final class Data
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromRaw(string $raw): self
    {
        $value = json_decode($raw, true);
        if (!array_key_exists('type', $value) || $value['type'] !== 'data') {
            throw new \InvalidArgumentException('Invalid data');
        }

        return new self($value['value']);
    }

    public function toString(): string
    {
        return json_encode(['type' => 'data', 'value' => $this->value]);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
