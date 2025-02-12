<?php

declare(strict_types=1);

namespace Twee\Cache\Storage\Adapter;

use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\AbstractMetadataCapableAdapter;
use Laminas\Cache\Storage\StorageInterface;

/**
 * @template TKey
 * @template TValue
 * @implements IterableInterface<TKey, TValue>
 * @template-extends AbstractAdapter<AdapterOptions,object>
 */
final class Packer extends AbstractAdapter
{
    const MAX_ITEM_SIZE = 300000;

    private $storage = null;

    private $maxItemSize = self::MAX_ITEM_SIZE;

    public function __construct(StorageInterface $storage, int $maxItemSize = self::MAX_ITEM_SIZE)
    {
        $this->storage = $storage;
        $this->maxItemSize = $maxItemSize;
    }

    /**
     * {@inheritDoc}
     */
    protected function internalSetItem(string $normalizedKey, mixed $value): bool
    {
        $encodedValue = json_encode($value);
        if (strlen($encodedValue) < $this->maxItemSize) {
            return $this->storage->setItem($normalizedKey, Packer\Data::fromString($encodedValue)->toString());
        }
        
        $index = new Packer\Index();
        for ($i = 0; $i < strlen($encodedValue) / $this->maxItemSize; $i++) {
            $_key   = $normalizedKey . '::' . $i;
            $_value = substr($encodedValue, $i * $this->maxItemSize, $this->maxItemSize);
            $index->add($_key);
            $this->storage->setItem($_key, Packer\Data::fromString($_value)->toString());
        }

        return $this->storage->setItem($normalizedKey, $index->toString());
    }

    /**
     * {@inheritDoc}
     */
    protected function internalGetItem(
        string $normalizedKey,
        ?bool &$success = null,
        mixed &$casToken = null
    ): mixed
    {
        $raw = $this->storage->getItem($normalizedKey);
        if (!is_string($raw)) {
            $success = false;
            return null;
        }

        try {
            $success = true;
            return json_decode(Packer\Data::fromRaw($raw)->getValue(), true);
        } catch (\InvalidArgumentException $e) {
            try {

                $index = Packer\Index::fromRaw($raw);
                $raw = '';
                foreach ($index->getIndex() as $_key) {
                    $_value = $this->storage->getItem($_key);
                    $raw .= Packer\Data::fromRaw($_value)->getValue();
                }
                $success = true;
                return json_decode(Packer\Data::fromString($raw)->getValue(), true);
            } catch (\InvalidArgumentException $e) {
                $success = false;
                return false;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function internalRemoveItem(string $normalizedKey): bool
    {
        $raw = $this->storage->getItem($normalizedKey);
        if (!is_string($raw)) {
            return false;
        }

        try {
            Packer\Data::fromRaw($raw);
            return $this->storage->removeItem($normalizedKey);
        } catch (\InvalidArgumentException $e) {
            try {
                $index = Packer\Index::fromRaw($raw);
                $success = true;
                foreach ($index->getIndex() as $_key) {
                    $success = $this->storage->removeItem($_key) && $success;
                }
                return $this->storage->removeItem($normalizedKey) && $success;
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        }
    }
}
