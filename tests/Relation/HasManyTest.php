<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Relation;

use Platine\Database\ResultSet;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Relation\ForeignKey;
use Platine\Orm\Relation\HasMany;
use Platine\Dev\PlatineTestCase;
use Platine\Test\Fixture\Orm\Connection;

/**
 * HasMany class tests
 *
 * @group core
 * @group database
 */
class HasManyTest extends PlatineTestCase
{
    public function testGetResult(): void
    {
        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('all')
                ->will($this->returnValue([['id' => 1, 'user_id' => 3]]));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('query')
                ->will($this->returnValue($rs));

        $entityClass = get_class($this->getEntityInstance());

        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $entityMapper = $this->getEntityMapper([
            'getForeignKey' => $foreignKey,
            'getEntityClass' => $entityClass
        ], []);

        $entityManager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $entityManager->expects($this->exactly(1))
                ->method('getEntityMapper')
                ->with($entityClass);

        $dataMapper = $this->getDataMapper([
            'getEntityManager' => $entityManager,
            'getRawColumns' => ['user_id' => 1, 'id' => 9]
        ], []);

        $dataMapper->expects($this->exactly(1))
                ->method('getEntityManager');

        $dataMapper->expects($this->exactly(1))
                ->method('getRawColumns');

        $e = new HasMany(get_class($this->getEntityInstance()), $foreignKey);

        $queryCallback = 1;
        $callback = 1;

        $e->query(function () use (&$queryCallback) {
            $queryCallback = 2;
        });

        $result = $e->getResult($dataMapper, function () use (&$callback) {
            $callback = 2;
        });

        $this->assertCount(1, $result);
        $first = $result[0];
        $this->assertInstanceOf(Entity::class, $first);
        $this->assertEquals(1, $first->id);
        $this->assertEquals(3, $first->user_id);
        $this->assertEquals(2, $callback);
        $this->assertEquals(2, $queryCallback);
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
