<?php

namespace Twee\Cache\Storage\Adapter\Packer;

final class Index
{
    private array $index = [];

    public function __construct(array $index = [])
    {
        $this->index = $index;
    }

    public function add(string $key): void
    {
        $this->index[] = $key;
    }

    public static function fromRaw(string $raw): self
    {
        $value = json_decode($raw, true);
        if (!is_array($value) || $value['type'] !== 'index') {
            throw new \InvalidArgumentException('Invalid index');
        }
        return new self($value['value']);
    }

    public function toString(): string
    {
        return json_encode(['type' => 'index', 'value' => $this->index]);
    }

    public function getIndex(): array
    {
        return $this->index;
    }   
}