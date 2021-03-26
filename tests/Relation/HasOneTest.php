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
use Platine\Orm\Relation\HasOne;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\RelationLoader;
use Platine\PlatineTestCase;
use Platine\Test\Fixture\Orm\Connection;

/**
 * HasOne class tests
 *
 * @group core
 * @group database
 */
class HasOneTest extends PlatineTestCase
{
    public function testAddRelatedEntityWhereForeignIsNull(): void
    {
        $entity = $this->getEntityInstance(['id' => 1, 'foo_id' => 2]);
        $entityClass = get_class($entity);
        $e = new HasOne($entityClass);

        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $entityMapper = $this->getEntityMapper([
            'getForeignKey' => $foreignKey
        ], []);


        $dataMapper = $this->getDataMapper([
            'getEntityMapper' => $entityMapper,
            'getRawColumns' => ['id' => 1, 'user_id' => 2]
        ], []);

        $dataMapper->expects($this->exactly(1))
                ->method('getEntityMapper');


        $entityMapper->expects($this->exactly(1))
                ->method('getForeignKey');


        $e->addRelatedEntity($dataMapper, $entity);
    }

    public function testAddRelatedEntityWhereForeignIsNotNull(): void
    {

        $entityMapper = $this->getEntityMapper([], []);

        $dataMapper = $this->getDataMapper([
            'getEntityMapper' => $entityMapper,
            'getRawColumns' => ['id' => 1, 'user_id' => 2]
        ], []);

        $dataMapper->expects($this->exactly(1))
                ->method('getEntityMapper');

        $entity = $this->getEntityInstance(['id' => 1, 'foo_id' => 2]);
        $entityClass = get_class($entity);

        $dataMapper->expects($this->exactly(1))
                ->method('getRawColumns');

        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $e = new HasOne($entityClass, $foreignKey);

        $e->addRelatedEntity($dataMapper, $entity);
    }

    public function testGetLoader(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new HasOne($entityClass);

        $foreignKey = $this->getMockBuilder(ForeignKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $foreignKey->expects($this->any())
                ->method('getInverseValue')
                ->will($this->onConsecutiveCalls(['user_id' => 1], ['user_id' => 2]));

        $entityMapper = $this->getEntityMapper([
            'getForeignKey' => $foreignKey
        ], []);

        $entityManager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper
        ], []);

        $entityManager->expects($this->exactly(1))
                ->method('getEntityMapper')
                ->with($entityClass);


        $result = $e->getLoader($entityManager, $entityMapper, [
            'results' => [
                ['id' => 1, 'name' => 'foo'],
                ['id' => 2, 'name' => 'bar'],
                ['id' => 3, 'name' => 'foobar']
            ],
            'callback' => null,
            'with' => [],
            'immediate' => false
        ]);

        $this->assertInstanceOf(RelationLoader::class, $result);

        $rInverse = $this->getPrivateProtectedAttribute(RelationLoader::class, 'inverse');
        $rResults = $this->getPrivateProtectedAttribute(RelationLoader::class, 'results');
        $rKeys = $this->getPrivateProtectedAttribute(RelationLoader::class, 'keys');
        $rHasMany = $this->getPrivateProtectedAttribute(RelationLoader::class, 'hasMany');

        $this->assertFalse($rInverse->getValue($result));
        $this->assertFalse($rHasMany->getValue($result));
        $this->assertCount(0, $rResults->getValue($result));
        $this->assertEmpty($rKeys->getValue($result));
    }

    public function testGetLoaderCustomerForeignKey(): void
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
                ->method('getInverseValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $foreignKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $foreignKey->expects($this->any())
                ->method('extractValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1, 'user_id' => 2]));

        $entityMapper = $this->getEntityMapper([
            'getForeignKey' => $foreignKey,
            'getPrimaryKey' => $primaryKey,
            'getEntityClass' => $entityClass
        ], []);

        $entityManager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $entityManager->expects($this->exactly(1))
                ->method('getEntityMapper')
                ->with($entityClass);

        $entityMapper->expects($this->exactly(1))
                ->method('getPrimaryKey');

        $e = new HasOne(get_class($this->getEntityInstance()), $foreignKey);

        $result = $e->getLoader($entityManager, $entityMapper, [
            'results' => [['id' => 1, 'name' => 'foo']],
            'callback' => function () {
            },
            'with' => [],
            'immediate' => true
        ]);

        $this->assertInstanceOf(RelationLoader::class, $result);

        $rInverse = $this->getPrivateProtectedAttribute(RelationLoader::class, 'inverse');
        $rResults = $this->getPrivateProtectedAttribute(RelationLoader::class, 'results');
        $rKeys = $this->getPrivateProtectedAttribute(RelationLoader::class, 'keys');
        $rHasMany = $this->getPrivateProtectedAttribute(RelationLoader::class, 'hasMany');

        $this->assertFalse($rInverse->getValue($result));
        $this->assertFalse($rHasMany->getValue($result));
        $this->assertCount(1, $rResults->getValue($result));
        $keys = $rKeys->getValue($result);
        $this->assertCount(1, $keys);
        $this->assertIsArray($keys[0]);
        $this->assertArrayHasKey('id', $keys[0]);
        $this->assertArrayHasKey('user_id', $keys[0]);
    }

    public function testGetResultCallbackAndForeignKeyNull(): void
    {
        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('get')
                ->will($this->returnValue(false));

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
            'getEntityClass' => $entityClass,
            'getForeignKey' => $foreignKey,
        ], []);

        $entityMapper->expects($this->exactly(1))
                ->method('getForeignKey');

        $entityManager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx
        ], []);


        $entityManager->expects($this->exactly(1))
                ->method('getEntityMapper')
                ->with($entityClass);

        $dataMapper = $this->getDataMapper([
            'getEntityManager' => $entityManager,
            'getRawColumns' => ['user_id' => 1, 'id' => 9],
            'getEntityMapper' => $entityMapper,
        ], []);

        $dataMapper->expects($this->exactly(1))
                ->method('getEntityManager');

        $dataMapper->expects($this->exactly(1))
                ->method('getEntityMapper');

        $dataMapper->expects($this->exactly(1))
                ->method('getRawColumns');

        $e = new HasOne(get_class($this->getEntityInstance()));

        $result = $e->getResult($dataMapper, null);

        $this->assertNull($result);
    }

    public function testGetResult(): void
    {
        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('get')
                ->will($this->returnValue(['id' => 1, 'user_id' => 3]));

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

        $e = new HasOne(get_class($this->getEntityInstance()), $foreignKey);

        $queryCallback = 1;
        $callback = 1;

        $e->query(function () use (&$queryCallback) {
            $queryCallback = 2;
        });

        $result = $e->getResult($dataMapper, function () use (&$callback) {
            $callback = 2;
        });

        $this->assertInstanceOf(Entity::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals(3, $result->user_id);
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
