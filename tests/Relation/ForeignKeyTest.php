<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Relation;

use Platine\Orm\Relation\ForeignKey;
use Platine\Dev\PlatineTestCase;

/**
 * ForeignKey class tests
 *
 * @group core
 * @group database
 */
class ForeignKeyTest extends PlatineTestCase
{
    public function testConstructorNonComposite(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = $e->columns();
        $this->assertCount(1, $columns);
        $this->assertArrayHasKey('user_id', $columns);
        $this->assertEquals('id', $columns['user_id']);
        $this->assertEquals('id', $e->__toString());
        $this->assertFalse($e->isComposite());
    }

    public function testConstructorComposite(): void
    {

        $e = new ForeignKey([
            'user_id' => 'id',
            'bar_id' => 'fid'
        ]);

        $columns = $e->columns();
        $this->assertCount(2, $columns);
        $this->assertArrayHasKey('user_id', $columns);
        $this->assertArrayHasKey('bar_id', $columns);
        $this->assertEquals('id', $columns['user_id']);
        $this->assertEquals('fid', $columns['bar_id']);
        $this->assertEquals('id, fid', $e->__toString());
        $this->assertTrue($e->isComposite());
    }

    public function testGetValue(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'user_id' => 5,
            'id' => 10
        ];
        $result = $e->getValue($columns, false);
        $this->assertEquals(5, $result);
        $result = $e->getValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(5, $result['id']);
    }

    public function testGetValueNull(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'name' => 'foo',
            'status' => true
        ];

        $result = $e->getValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNull($result['id']);
    }

    public function testGetInverseValue(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'user_id' => 5,
            'id' => 10
        ];
        $result = $e->getInverseValue($columns, false);
        $this->assertEquals(10, $result);
        $result = $e->getInverseValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals(10, $result['user_id']);
    }

    public function testGetInverseValueNull(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'name' => 'foo',
            'status' => true
        ];

        $result = $e->getInverseValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertNull($result['user_id']);
    }

    public function testExtractValue(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'user_id' => 5,
            'id' => 10
        ];
        $result = $e->extractValue($columns, false);
        $this->assertEquals(10, $result);
        $result = $e->extractValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(10, $result['id']);
    }

    public function testExtractValueNull(): void
    {

        $e = new ForeignKey(['user_id' => 'id']);

        $columns = [
            'name' => 'foo',
            'status' => true
        ];

        $result = $e->extractValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNull($result['id']);
    }
}
