<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Query;

use Platine\Database\Query\HavingStatement;
use Platine\Database\Query\QueryStatement;
use Platine\Database\ResultSet;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\Relation\BelongsTo;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\RelationLoader;
use Platine\Dev\PlatineTestCase;
use Platine\Test\Fixture\Orm\Connection;

/**
 * EntityQuery class tests
 *
 * @group core
 * @group database
 */
class EntityQueryTest extends PlatineTestCase
{
    public function testConstruct(): void
    {
        $manager = $this->getEntityManager([], []);
        $entityMapper = $this->getEntityMapper([], []);
        $e = new EntityQuery($manager, $entityMapper, null);

        $rManager = $this->getPrivateProtectedAttribute(EntityQuery::class, 'manager');
        $rMapper = $this->getPrivateProtectedAttribute(EntityQuery::class, 'mapper');

        $this->assertInstanceOf(QueryStatement::class, $e->getQueryStatement());
        $this->assertInstanceOf(EntityManager::class, $rManager->getValue($e));
        $this->assertInstanceOf(EntityMapper::class, $rMapper->getValue($e));
    }

    public function testClone(): void
    {
        $manager = $this->getEntityManager([], []);
        $entityMapper = $this->getEntityMapper([], []);
        $e = new EntityQuery($manager, $entityMapper, null);

        $c = clone $e;
        $rHaving = $this->getPrivateProtectedAttribute(EntityQuery::class, 'havingStatement');

        $this->assertInstanceOf(HavingStatement::class, $rHaving->getValue($c));
    }

    public function testFilter(): void
    {
        $callbackResult = 1;
        $cb = function () use (&$callbackResult) {
            $callbackResult = 2;
        };

        $entityMapper = $this->getEntityMapper([
            'getFilters' => ['my_filter' => $cb]
        ], []);

        $manager = $this->getEntityManager([], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->filter('my_filter');

        $this->assertEquals(2, $callbackResult);
    }

    public function testDelete(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->delete();

        $this->assertEquals('DELETE FROM `foo`', $cnx->getRawSql());
    }

    public function testDeleteUsingSoftDelete(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo',
            'hasSoftDelete' => true,
            'getSoftDeleteColumn' => 'deleted',
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->delete();

        $this->assertEquals("UPDATE `foo` SET `deleted` = '" . date('Y-m') . "'", $cnx->getRawSql());
    }

    public function testQueryWithSoftDelete(): void
    {
        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'hasSoftDelete' => true,
            'getSoftDeleteColumn' => 'deleted',
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager(
            [
             'getConnection' => $cnx
            ],
            []
        );


        $e = new EntityQuery($manager, $entityMapper, null);

        $res = $this->runPrivateProtectedMethod($e, 'query', [[
            'status', 'name'
        ]]);

        $this->assertInstanceOf(ResultSet::class, $res);
        $this->assertEquals('SELECT `status`, `name`, `id` FROM `foo` WHERE `deleted` IS NULL', $cnx->getRawSql());
    }

    public function testQueryOnlyWithSoftDelete(): void
    {
        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'hasSoftDelete' => true,
            'getSoftDeleteColumn' => 'deleted',
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager(
            [
             'getConnection' => $cnx
            ],
            []
        );


        $e = new EntityQuery($manager, $entityMapper, null);
        $e->onlyDeleted();

        $res = $this->runPrivateProtectedMethod($e, 'query', [[
            'status', 'name'
        ]]);

        $this->assertInstanceOf(ResultSet::class, $res);
        $this->assertEquals('SELECT `status`, `name`, `id` FROM `foo` WHERE `deleted` IS NOT NULL', $cnx->getRawSql());
    }

    public function testUpdate(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->update(['foo' => 'bar','name' => 123]);

        $this->assertEquals('UPDATE `foo` SET `foo` = \'bar\', `name` = 123', $cnx->getRawSql());
    }

    public function testUpdateUsingTimestamps(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->update(['foo' => 'bar','name' => 123]);

        $this->assertEquals("UPDATE `foo` SET `foo` "
                . "= 'bar', `name` = 123, `u_at` = '" . date('Y-m')
                . "'", $cnx->getRawSql());
    }

    public function testIncrementUsingTimestamps(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $e->increment('foo');
        $this->assertEquals("UPDATE `foo` SET `u_at` = '" . date('Y-m') . "', `foo` = `foo` + 1", $cnx->getRawSql());

        $e = new EntityQuery($manager, $entityMapper, null);
        $e->decrement('foo');
        $this->assertEquals("UPDATE `foo` SET `u_at` = '" . date('Y-m') . "', `foo` = `foo` - 1", $cnx->getRawSql());
    }

    public function testFindNoResult(): void
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

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getEntityClass' => get_class($this->getEntityInstance()),
            'getTable' => 'foo',
            'getPrimaryKey' => $primaryKey,
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $res = $e->find(1);

        $this->assertNull($res);
    }

    public function testFind(): void
    {
        $this->findTests(1);
        $this->findTests(['id' => 1, 'name' => 'foo']);
    }

    public function testFindAll(): void
    {
        $this->findAllTests(...[1, 2]);
        $this->findAllTests(...[['id' => [1, 3, 45]]]);
    }

    public function testColumnWithSoftDelete(): void
    {
        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'hasSoftDelete' => true,
            'getSoftDeleteColumn' => 'deleted',
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager(
            [
             'getConnection' => $cnx
            ],
            []
        );

        $qs = $this->getMockBuilder(QueryStatement::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $qs->expects($this->exactly(1))
                ->method('addColumn');

        $e = new EntityQuery($manager, $entityMapper, $qs);

        $e->column('bar');
    }


    public function testColumnOnlySoftDelete(): void
    {
        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'hasSoftDelete' => true,
            'getSoftDeleteColumn' => 'deleted',
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager(
            [
             'getConnection' => $cnx
            ],
            []
        );

        $qs = $this->getMockBuilder(QueryStatement::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $qs->expects($this->exactly(1))
                ->method('addColumn');

        $e = new EntityQuery($manager, $entityMapper, $qs);

        $e->onlyDeleted()->column('bar');
    }

    public function testGetRelationLoadersEmpty(): void
    {
        $entityMapper = $this->getEntityMapper([], []);

        $manager = $this->getEntityManager([], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $res = $this->runPrivateProtectedMethod($e, 'getRelationLoaders', [[]]);
        $this->assertEmpty($res);
    }

    public function testGetRelationLoadersWithNotExistInRelation(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityMapper = $this->getEntityMapper([
            'getRelations' => ['bar' => $bt]
        ], []);

        $manager = $this->getEntityManager([], []);


        $e = new EntityQuery($manager, $entityMapper, null);
        $e->with('foo');

        $res = $this->runPrivateProtectedMethod($e, 'getRelationLoaders', [[['one' => 1]]]);
        $this->assertEmpty($res);
    }

    public function testGetRelationLoaders(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityMapper = $this->getEntityMapper([
            'getRelations' => ['bar' => $bt]
        ], []);

        $manager = $this->getEntityManager([], []);


        $e = new EntityQuery($manager, $entityMapper, null);
        $e->with('bar');

        $res = $this->runPrivateProtectedMethod($e, 'getRelationLoaders', [[['one' => 1]]]);
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('bar', $res);
        $this->assertInstanceOf(RelationLoader::class, $res['bar']);
    }

    public function testGetRelationLoadersUsingExtra(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityMapper = $this->getEntityMapper([
            'getRelations' => ['bar' => $bt]
        ], []);

        $manager = $this->getEntityManager([], []);


        $e = new EntityQuery($manager, $entityMapper, null);
        $e->with(['bar', 'foo.bar']);

        $res = $this->runPrivateProtectedMethod($e, 'getRelationLoaders', [[['one' => 1]]]);
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('bar', $res);
        $this->assertInstanceOf(RelationLoader::class, $res['bar']);
    }

    public function testAggregate(): void
    {
        $cnx = new Connection('MySQL');

        $entityMapper = $this->getEntityMapper([
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);

        $qs = $this->getMockBuilder(QueryStatement::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $qs->expects($this->exactly(5))
                ->method('addColumn');

        $e = new EntityQuery($manager, $entityMapper, $qs);

        $e->count('bar');
        $e->avg('bar');
        $e->sum('bar');
        $e->min('bar');
        $e->max('bar');
    }

    private function findTests($primaryKeys): void
    {
        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('get')
                ->will($this->returnValue([['id' => 1, 'user_id' => 3]]));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('query')
                ->will($this->returnValue($rs));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getEntityClass' => get_class($this->getEntityInstance()),
            'getTable' => 'foo',
            'getPrimaryKey' => $primaryKey,
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $res = $e->find($primaryKeys);

        $this->assertInstanceOf(Entity::class, $res);
    }

    private function findAllTests(...$primaryKeys): void
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

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getEntityClass' => get_class($this->getEntityInstance()),
            'getTable' => 'foo',
            'getPrimaryKey' => $primaryKey,
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'getConnection' => $cnx,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new EntityQuery($manager, $entityMapper, null);

        $res = $e->findAll(...$primaryKeys);

        $this->assertCount(1, $res);
        $this->assertInstanceOf(Entity::class, $res[0]);
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
