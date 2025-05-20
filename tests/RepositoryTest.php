<?php

declare(strict_types=1);

namespace Platine\Test\Orm;

use Platine\Database\Query\Where;
use Platine\Database\Query\WhereStatement;
use Platine\Dev\PlatineTestCase;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Exception\EntityStateException;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\ShareOne;
use Platine\Orm\Repository;
use Platine\Test\Fixture\Orm\Connection;

/**
 * Repository class tests
 *
 * @group core
 * @group database
 */
class RepositoryTest extends PlatineTestCase
{
    public function testConstruct(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([], []);

        $e = new Repository($manager, $entityClass);

        $rManager = $this->getPrivateProtectedAttribute(Repository::class, 'manager');
        $rEntityClass = $this->getPrivateProtectedAttribute(Repository::class, 'entityClass');

        $this->assertInstanceOf(EntityManager::class, $rManager->getValue($e));
        $this->assertEquals($entityClass, $rEntityClass->getValue($e));
    }

    public function testQuery(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->query();
        $this->assertInstanceOf(EntityQuery::class, $res);
    }


    public function testQueryWith(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $with = 'foo';
        $immadiate = true;

        $eq->expects($this->exactly(1))
                ->method('with')
                ->with([$with], $immadiate)
                ->will($this->returnSelf());

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->query($with, $immadiate);
        $this->assertInstanceOf(EntityQuery::class, $res);
    }

    public function testUsingWith(): void
    {
        $with = 'foo';
        $immadiate = true;

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([], []);

        $e = new Repository($manager, $entityClass);
        $res = $e->with($with, $immadiate);
        $this->assertInstanceOf(Repository::class, $res);

        $rWith = $this->getPrivateProtectedAttribute(Repository::class, 'with');
        $rImmediate = $this->getPrivateProtectedAttribute(Repository::class, 'immediate');

        $this->assertEquals([$with], $rWith->getValue($res));
        $this->assertTrue($rImmediate->getValue($res));
    }

    public function testAll(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('all')
                ->will($this->returnValue([]));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->all();
        $this->assertEmpty($res);
    }

    public function testCreate(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([], []);


        $e = new Repository($manager, $entityClass);
        $res = $e->create(['one' => 1, 'two' => 2]);
        $this->assertInstanceOf(Entity::class, $res);
        $this->assertEquals(1, $res->one);
        $this->assertEquals(2, $res->two);
    }

    public function testFind(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('find')
                ->will($this->returnValue(null));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->find(1);
        $this->assertNull($res);
    }

    public function testFindWith(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('find')
                ->will($this->returnValue(null));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->with('foo')->find(1);
        $this->assertNull($res);

        $rWith = $this->getPrivateProtectedAttribute(Repository::class, 'with');
        $rImmediate = $this->getPrivateProtectedAttribute(Repository::class, 'immediate');

        //Already reset after each query
        $this->assertEmpty($rWith->getValue($e));
        $this->assertFalse($rImmediate->getValue($e));
    }

    public function testFindUsingFilters(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('filter')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('find')
                ->will($this->returnValue(null));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->filters('foo')->find(1);
        $this->assertNull($res);

        //Already reset after each query
        $this->assertEmpty($this->getPropertyValue(Repository::class, $e, 'filters'));
    }

    public function testFindOrderBy(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('orderBy')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('find')
                ->will($this->returnValue(null));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->orderBy('foo')
                ->find(1);
        $this->assertNull($res);

        //Already reset after each query
        $this->assertEmpty($this->getPropertyValue(Repository::class, $e, 'orderColumns'));
        $this->assertEquals('ASC', $this->getPropertyValue(Repository::class, $e, 'orderDir'));
    }

    public function testFindUsingLimit(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('offset')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('limit')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('find')
                ->will($this->returnValue(null));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->limit(0, 10)
                ->find(1);
        $this->assertNull($res);

        //Already reset after each query
        $this->assertEquals(-1, $this->getPropertyValue(Repository::class, $e, 'offset'));
        $this->assertEquals(0, $this->getPropertyValue(Repository::class, $e, 'limit'));
    }

    public function testFindBy(): void
    {
        $ws = $this->getMockInstance(WhereStatement::class);
        $where = $this->getMockInstance(Where::class, [
            'is' => $ws,
        ]);

        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('get')
                ->will($this->returnValue(null));

        $eq->expects($this->exactly(2))
                ->method('where')
                ->will($this->returnValue($where));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->findBy(['id' => 1, 'name' => 'foo']);
        $this->assertNull($res);
    }

    public function testFindAllBy(): void
    {
        $ws = $this->getMockInstance(WhereStatement::class);
        $where = $this->getMockInstance(Where::class, [
            'is' => $ws,
        ]);

        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('all')
                ->will($this->returnValue([]));

        $eq->expects($this->exactly(2))
                ->method('where')
                ->will($this->returnValue($where));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->findAllBy(['id' => 1, 'name' => 'bar']);
        $this->assertEmpty($res);
    }

    public function testFindAll(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eq->expects($this->exactly(1))
                ->method('with')
                ->will($this->returnSelf());

        $eq->expects($this->exactly(1))
                ->method('findAll')
                ->will($this->returnValue([]));

        $entityClass = get_class($this->getEntityInstance([]));
        $manager = $this->getEntityManager([
            'query' => $eq
        ], []);

        $manager->expects($this->exactly(1))
                ->method('query')
                ->with($entityClass);

        $e = new Repository($manager, $entityClass);
        $res = $e->findAll(1, 2, 3);
        $this->assertEmpty($res);
    }

    public function testSave(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);
        $this->assertEquals('INSERT INTO `my_table`(`name`) VALUES (\'foo\')', $cnx->getRawSql());
    }

    public function testSaveUsingEventHandler(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $handlerValue = 1;

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'getEventHandlers' => ['save' => [
                function () use (&$handlerValue) {
                    $handlerValue = 2;
                }
            ]],
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);
        $this->assertEquals(2, $handlerValue);
        $this->assertEquals('INSERT INTO `my_table`(`name`) VALUES (\'foo\')', $cnx->getRawSql());
    }


    public function testSaveUsingPrimaryKeyGenerator(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKeyGenerator' => function () {
                return 123;
            },
            'getTable' => 'my_table',
            'getPrimaryKey' => $primaryKey,
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);
        $this->assertEquals('INSERT INTO `my_table`(`name`, `id`) VALUES (\'foo\', 123)', $cnx->getRawSql());
    }

    public function testSaveUsingPrimaryKeyGeneratorReturnNull(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKeyGenerator' => function () {
                return null;
            },
            'getTable' => 'my_table',
            'getPrimaryKey' => $primaryKey,
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertFalse($res);
    }

    public function testSaveUsingPrimaryKeyGeneratorMultiple(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKeyGenerator' => function () {
                return ['one' => 1, 'two' => 2];
            },
            'getTable' => 'my_table',
            'getPrimaryKey' => $primaryKey,
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);
        $this->assertEquals('INSERT INTO `my_table`(`name`, `one`, `two`) VALUES (\'foo\', 1, 2)', $cnx->getRawSql());
    }

    public function testSaveUsingTimestamp(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper,
            'getDateFormat' => 'Y-m',
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);
        $this->assertEquals("INSERT INTO `my_table`(`name`, `c_at`, `u_at`)"
                . " VALUES ('foo', '" . date('Y-m') . "', NULL)", $cnx->getRawSql());
    }

    public function testInsertRecordWasDeleted(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $entityMapper = $this->getEntityMapper([
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $dr = $this->getPrivateProtectedAttribute(DataMapper::class, 'deleted');
        $dr->setValue($dmr, true);

        $e->insert($entity);
    }

    public function testUpdateRecordWasDeleted(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $entityMapper = $this->getEntityMapper([
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $dr = $this->getPrivateProtectedAttribute(DataMapper::class, 'deleted');
        $dr->setValue($dmr, true);

        $e->update($entity);
    }

    public function testSaveNoNewAndMockExecutePendingLink(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $sh = $this->getMockBuilder(ShareOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');

        $nr = $this->getPrivateProtectedAttribute(DataMapper::class, 'isNew');
        $nr->setValue($dmr, false);

        $plr = $this->getPrivateProtectedAttribute(DataMapper::class, 'pendingLinks');
        $plr->setValue($dmr, [[
            'entity' => $entity,
            'link' => false,
            'relation' => $sh
        ]]);

        $this->assertCount(1, $plr->getValue($dmr));
        $e->save($entity);
        $this->assertCount(0, $plr->getValue($dmr));
    }


    public function testDeleteRecordWasDeleted(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $dr = $this->getPrivateProtectedAttribute(DataMapper::class, 'deleted');
        $dr->setValue($dmr, true);

        $e->delete($entity);
    }

    public function testDeleteRecordIsNew(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $nr = $this->getPrivateProtectedAttribute(DataMapper::class, 'isNew');
        $nr->setValue($dmr, true);

        $e->delete($entity);
    }

    public function testInsertRecordIsNotNew(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $nr = $this->getPrivateProtectedAttribute(DataMapper::class, 'isNew');
        $nr->setValue($dmr, false);

        $e->insert($entity);
    }

    public function testUpdateRecordIsNew(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);


        $this->expectException(EntityStateException::class);

        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $dmr = $this->runPrivateProtectedMethod($entity, 'mapper');
        $nr = $this->getPrivateProtectedAttribute(DataMapper::class, 'isNew');
        $nr->setValue($dmr, true);

        $e->update($entity);
    }

    public function testDeleteReturnFalse(): void
    {
        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = new Connection('MySQL');

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'foo'
        ], []);

        $manager = $this->getEntityManager([
            'getEntityMapper' => $entityMapper,
            'getConnection' => $cnx,
        ], []);



        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        //False because we didnt mock the return of Update::set()
        $this->assertFalse($e->delete($entity));
        $this->assertEquals("DELETE FROM `foo` WHERE `id` = 1", $cnx->getRawSql());
    }

    public function testDeleteWithEventHandlers(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('count')
                ->will($this->returnValue(12));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $handlerValue = 1;

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'getEventHandlers' => ['delete' => [
                function () use (&$handlerValue) {
                    $handlerValue = 2;
                }
            ]]
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper,
        ], []);



        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $this->assertTrue($e->delete($entity));
        $this->assertEquals(2, $handlerValue);
    }

    public function testUpdateWithTimestampAndEvent(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();


        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper,
            'getDateFormat' => 'Y-m',
        ], []);



        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $entity->name = 'baz';

        $this->assertTrue($e->save($entity));
        $this->assertEquals(
            "UPDATE `my_table` SET `name` = "
                . "'baz', `u_at` = '" . date('Y-m') . "' WHERE `id` = 1",
            $cnx->getRawSql()
        );
    }

    public function testUpdateWithEventHandlers(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityClass = get_class($this->getEntityInstance([]));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('count')
                ->will($this->returnValue(12));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $handlerValue = 1;

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
            'getEventHandlers' => ['update' => [
                function () use (&$handlerValue) {
                    $handlerValue = 2;
                }
            ]]
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper,
            'getDateFormat' => 'Y-m',
        ], []);



        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $entity->name = 'baz';

        $this->assertTrue($e->save($entity));
        $this->assertEquals(2, $handlerValue);
    }

    public function testUpdateWithEventHandlersResultFailed(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $entityClass = get_class($this->getEntityInstance([]));

           $cnx = $this->getMockInstance(Connection::class, [
               'transaction' => false,
           ]);


        $cnx->expects($this->any())
                ->method('count')
                ->will($this->returnValue(12));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $handlerValue = 1;

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
            'hasTimestamp' => true,
            'getTimestampColumns' => ['c_at','u_at'],
            'getEventHandlers' => ['update' => [
                function () use (&$handlerValue) {
                    $handlerValue = 2;
                }
            ]]
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper,
            'getDateFormat' => 'Y-m',
        ], []);



        $e = new Repository($manager, $entityClass);

        $entity = $this->getEntityInstance([], $manager, $entityMapper);

        $entity->name = 'baz';

        $this->assertFalse($e->save($entity));
        $this->assertEquals(1, $handlerValue);
    }

    public function testUpdateNotModified(): void
    {
        $eq = $this->getMockBuilder(EntityQuery::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx = new Connection('MySQL');

        $entityClass = get_class($this->getEntityInstance([]));

        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $primaryKey->expects($this->any())
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $entityMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table',
        ], []);

        $manager = $this->getEntityManager([
            'query' => $eq,
            'getConnection' => $cnx,
            'getEntityMapper' => $entityMapper
        ], []);


        $e = new Repository($manager, $entityClass);

        $entity = $e->create([
            'name' => 'foo'
        ]);

        $res = $e->save($entity);
        $this->assertTrue($res);

        //No modification
        $this->assertTrue($e->save($entity));
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

    private function getEntityInstance(
        array $columns = [],
        EntityManager $em = null,
        EntityMapper $mapper = null
    ): Entity {
        if (!$em) {
            $em = $this->getMockBuilder(EntityManager::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        }

        if (!$mapper) {
            $mapper = $this->getMockBuilder(EntityMapper::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        }
        return new class ($em, $mapper, $columns) extends Entity
        {
            public static function mapEntity(EntityMapperInterface $mapper): void
            {
            }
        };
    }
}
