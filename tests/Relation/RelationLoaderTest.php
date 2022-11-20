<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Relation;

use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\Relation\ForeignKey;
use Platine\Orm\Relation\RelationLoader;
use Platine\Dev\PlatineTestCase;

/**
 * RelationLoader class tests
 *
 * @group core
 * @group database
 */
class RelationLoaderTest extends PlatineTestCase
{
    public function testGetResultMany(): void
    {
        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));


        $foreignKey->expects($this->any())
                ->method('extractValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $dataMapper = $this->getDataMapper([
            'getRawColumns' => ['id' => 1, 'user_id' => 2]
        ], []);


        $entityQuery = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityQuery->expects($this->exactly(1))
                ->method('all')
                ->will($this->returnValue([
                    $this->getEntityInstance([
                    'id' => 1, 'user_id' => 2
                    ]),
                ]));

        $e = new RelationLoader($entityQuery, $foreignKey, true, true, true);


        $result = $e->getResult($dataMapper);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Entity::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals(2, $result[0]->user_id);
    }

    public function testGetResultSingle(): void
    {
        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));


        $foreignKey->expects($this->any())
                ->method('extractValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $dataMapper = $this->getDataMapper([
            'getRawColumns' => ['id' => 1, 'user_id' => 2]
        ], []);


        $entityQuery = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityQuery->expects($this->exactly(1))
                ->method('all')
                ->will($this->returnValue([
                    $this->getEntityInstance([
                    'id' => 1, 'user_id' => 2
                    ]),
                ]));

        $e = new RelationLoader($entityQuery, $foreignKey, true, false, true);


        $result = $e->getResult($dataMapper);

        $this->assertInstanceOf(Entity::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(2, $result->user_id);
    }

    public function testGetResultNull(): void
    {
        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 71, 'user_id' => 2]));


        $foreignKey->expects($this->any())
                ->method('extractValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $dataMapper = $this->getDataMapper([
            'getRawColumns' => ['id' => 11, 'user_id' => 12]
        ], []);


        $entityQuery = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityQuery->expects($this->exactly(1))
                ->method('all')
                ->will($this->returnValue([
                    $this->getEntityInstance([
                    'id_none' => 11, 'user_none_id' => 2
                    ]),
                ]));

        $e = new RelationLoader($entityQuery, $foreignKey, false, false, true);


        $result = $e->getResult($dataMapper);

        $this->assertNull($result);
    }

    private function getDataMapper(array $mockMethods = [], array $excludes = []): DataMapper
    {
        $methods = $this->getClassMethodsToMock(DataMapper::class, $excludes);

        $dm = $this->getMockBuilder(DataMapper::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods($methods)
                    ->getMock();

        foreach ($mockMethods as $method => $returnValue) {
            $dm->expects($this->any())
                ->method($method)
                ->will($this->returnValue($returnValue));
        }

        return $dm;
    }

    private function getEntityManager(array $mockMethods = [], array $excludes = []): EntityManager
    {
        $methods = $this->getClassMethodsToMock(EntityManager::class, $excludes);

        $em = $this->getMockBuilder(EntityManager::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods($methods)
                    ->getMock();

        foreach ($mockMethods as $method => $returnValue) {
            $em->expects($this->any())
                ->method($method)
                ->will($this->returnValue($returnValue));
        }

        return $em;
    }

    private function getEntityMapper(array $mockMethods = [], array $excludes = []): EntityMapper
    {
        $methods = $this->getClassMethodsToMock(EntityMapper::class, $excludes);

        $em = $this->getMockBuilder(EntityMapper::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods($methods)
                    ->getMock();

        foreach ($mockMethods as $method => $returnValue) {
            $em->expects($this->any())
                ->method($method)
                ->will($this->returnValue($returnValue));
        }

        return $em;
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
