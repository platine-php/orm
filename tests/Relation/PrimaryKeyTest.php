<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Relation;

use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Dev\PlatineTestCase;

/**
 * PrimaryKey class tests
 *
 * @group core
 * @group database
 */
class PrimaryKeyTest extends PlatineTestCase
{
    public function testConstructorNonComposite(): void
    {

        $e = new PrimaryKey('id');

        $columns = $e->columns();
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns[0]);
        $this->assertEquals('id', $e->__toString());
        $this->assertFalse($e->isComposite());
    }

    public function testConstructorComposite(): void
    {

        $e = new PrimaryKey('id', 'created_at');

        $columns = $e->columns();
        $this->assertCount(2, $columns);
        $this->assertEquals('id', $columns[0]);
        $this->assertEquals('created_at', $columns[1]);
        $this->assertEquals('id, created_at', $e->__toString());
        $this->assertTrue($e->isComposite());
    }

    public function testGetValue(): void
    {

        $e = new PrimaryKey('id');

        $columns = [
            'name' => 'foo',
            'id' => 10
        ];
        $result = $e->getValue($columns, false);
        $this->assertEquals(10, $result);
        $result = $e->getValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(10, $result['id']);
    }

    public function testGetValueNull(): void
    {

        $e = new PrimaryKey('id');

        $columns = [
            'name' => 'foo',
            'status' => true
        ];

        $result = $e->getValue($columns, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertNull($result['id']);
    }

    public function testGetValueFromDataMapper(): void
    {
        $columns = [
            'name' => 'foo',
            'id' => 10
        ];

        $dm = $this->getMockBuilder(DataMapper::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $dm->expects($this->any())
            ->method('getRawColumns')
            ->will($this->returnValue($columns));

        $e = new PrimaryKey('id');

        $result = $e->getValueFromDataMapper($dm, false);
        $this->assertEquals(10, $result);

        $result = $e->getValueFromDataMapper($dm, true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(10, $result['id']);
    }

    public function testGetValueFromDataEntity(): void
    {
        $columns = [
            'name' => 'foo',
            'id' => 5
        ];
        $entity = $this->getEntityInstance($columns);

        $e = new PrimaryKey('id');

        $result = $e->getValueFromEntity($entity, false);
        $this->assertEquals(5, $result);

        $result = $e->getValueFromEntity($entity, true);
         $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(5, $result['id']);
    }




    private function getEntityInstance(array $columns = []): Entity
    {
        $em = $this->getMockBuilder(EntityManager::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $dm = $this->getMockBuilder(EntityMapper::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        return new class ($em, $dm, $columns) extends Entity
        {

            public static function mapEntity(EntityMapperInterface $mapper): void
            {
            }
        };
    }
}
