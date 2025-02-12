<?php

declare(strict_types=1);

namespace TweeTest\Cache\Storage\Adapter;

use Twee\Cache\Storage\Adapter\Packer;
use Twee\Cache\Storage\Adapter\Packer\Data;
use Twee\Cache\Storage\Adapter\Packer\Index;
use Laminas\Cache\Storage\Adapter\Memory;
use PHPUnit\Framework\TestCase;

class PackerTest extends TestCase
{
    private Memory $storage;
    private Packer $packer;
    
    protected function setUp(): void
    {
        $this->storage = new Memory();
        $this->packer = new Packer($this->storage);
    }

    public function testSetAndGetSmallItem(): void
    {
        $key = 'test-key';
        $value = ['foo' => 'bar'];
        $success = null;

        $result = $this->packer->setItem($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->packer->getItem($key, $success);
        $this->assertTrue($success);
        $this->assertEquals($value, $retrieved);
    }    

    public function testGetNonexistentItem(): void
    {
        $success = null;
        $result = $this->packer->getItem('nonexistent-key', $success);
        $this->assertFalse($success);
        $this->assertNull($result);
    }

    public function testRemoveItem(): void
    {
        $key = 'test-key';
        $value = ['foo' => 'bar'];
        
        $this->packer->setItem($key, $value);
        $result = $this->packer->removeItem($key);
        $this->assertTrue($result);
        
        $success = null;
        $retrieved = $this->packer->getItem($key, $success);
        $this->assertFalse($success);
        $this->assertNull($retrieved);
    }

    public function testRemoveLargeItem(): void
    {
        $key = 'test-key';
        $value = [
            'large_field' => str_repeat('a', Packer::MAX_ITEM_SIZE + 1000),
            'other_field' => 'some value'
        ];
        
        $this->packer->setItem($key, $value);
        $result = $this->packer->removeItem($key);
        $this->assertTrue($result);
        
        $success = null;
        $retrieved = $this->packer->getItem($key, $success);
        $this->assertFalse($success);
        $this->assertNull($retrieved);
    
    }

    public function testRemoveNonexistentItem(): void
    {
        $result = $this->packer->removeItem('nonexistent-key');
        $this->assertFalse($result);
    }

    public function testCustomMaxItemSize(): void
    {
        $smallerMaxSize = 1000;
        $packer = new Packer($this->storage, $smallerMaxSize);
        
        $key = 'test-key';
        $value = [
            'data' => str_repeat('a', $smallerMaxSize + 100)
        ];

        $result = $packer->setItem($key, $value);
        $this->assertTrue($result);

        $retrieved = $packer->getItem($key);
        $this->assertEquals($value, $retrieved, 'Item should be stored and retrieved correctly');
    }

    public function testSetAndGetComplexData(): void
    {
        $key = 'test-key';
        $value = [
            'string' => 'simple string',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'nested' => [
                'foo' => 'bar',
                'baz' => [
                    'qux' => 'quux'
                ]
            ],
            'large_field' => str_repeat('a', Packer::MAX_ITEM_SIZE / 2)
        ];
        $success = null;

        $result = $this->packer->setItem($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->packer->getItem($key, $success);
        $this->assertTrue($success);
        $this->assertEquals($value, $retrieved);
    }

    public function testSetAndGetMultipleLargeItems(): void
    {
        $items = [
            'key1' => ['data' => str_repeat('a', Packer::MAX_ITEM_SIZE + 500)],
            'key2' => ['data' => str_repeat('b', Packer::MAX_ITEM_SIZE + 1000)],
            'key3' => ['data' => str_repeat('c', Packer::MAX_ITEM_SIZE + 1500)]
        ];

        foreach ($items as $key => $value) {
            $result = $this->packer->setItem($key, $value);
            $this->assertTrue($result);
        }

        foreach ($items as $key => $value) {
            $success = null;
            $retrieved = $this->packer->getItem($key, $success);
            $this->assertTrue($success);
            $this->assertEquals($value, $retrieved);
        }
    }

    public function testBoundaryValueSize(): void
    {
        $key = 'test-key';
        $value = ['data' => str_repeat('a', Packer::MAX_ITEM_SIZE - 1)];
        $success = null;

        $result = $this->packer->setItem($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->packer->getItem($key, $success);
        $this->assertTrue($success);
        $this->assertEquals($value, $retrieved);

        // Test exactly at MAX_ITEM_SIZE
        $value = ['data' => str_repeat('a', Packer::MAX_ITEM_SIZE)];
        
        $result = $this->packer->setItem($key, $value);
        $this->assertTrue($result);

        $retrieved = $this->packer->getItem($key, $success);
        $this->assertTrue($success);
        $this->assertEquals($value, $retrieved);
    }
} 