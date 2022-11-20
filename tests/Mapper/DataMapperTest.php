<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Mapper;

use DateTime;
use Platine\Database\Connection;
use Platine\Database\ResultSet;
use Platine\Dev\PlatineTestCase;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Exception\EntityStateException;
use Platine\Orm\Exception\PropertyNotFoundException;
use Platine\Orm\Exception\RelationNotFoundException;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Relation\BelongsTo;
use Platine\Orm\Relation\HasOne;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\ShareOne;
use RuntimeException;
use stdClass;

/**
 * DataMapper class tests
 *
 * @group core
 * @group database
 */
class DataMapperTest extends PlatineTestCase
{
    public function testDefaultValues(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertInstanceOf(EntityManager::class, $e->getEntityManager());
        $this->assertInstanceOf(EntityMapper::class, $e->getEntityMapper());

        $this->assertEmpty($e->getModifiedColumns());
        $this->assertEmpty($e->getRawColumns());

        $this->assertFalse($e->isDeleted());
        $this->assertFalse($e->isReadOnly());
        $this->assertFalse($e->isNew());
        $this->assertFalse($e->wasModified());
        $this->assertFalse($e->hasColumn('foo_column'));
        $this->assertFalse($e->hasRelation('foo_relation'));
    }

    public function testConstructIntialValues(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertCount(2, $e->getModifiedColumns());
        $this->assertCount(2, $e->getRawColumns());

        $this->assertTrue($e->isNew());
        $this->assertTrue($e->wasModified());
        $this->assertTrue($e->hasColumn('foo'));
    }

    public function testGetColumnCached(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );


        $this->assertEquals('bar', $e->getColumn('foo'));
        $this->assertEquals('bar', $e->getColumn('foo'));
    }

    public function testGetRelatedNotFound(): void
    {
        $eMapper = $this->getEntityMapper([
            'getRelations' => []
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );


        $this->expectException(RelationNotFoundException::class);
        $e->getRelated('foo');
    }

    public function testSetRelatedNotFound(): void
    {
        $eMapper = $this->getEntityMapper([
            'getRelations' => []
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );


        $this->expectException(RelationNotFoundException::class);
        $e->setRelated('foo', null);
    }

    public function testGetRelatedCachedSimple(): void
    {
        $related = 123;

        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $bt->expects($this->any())
                ->method('getResult')
                ->will($this->returnValue($related));

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $bt]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals($related, $e->getRelated('foo'));
        $this->assertEquals($related, $e->getRelated('foo'));
    }

    public function testSetRelatedSuccess(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $bt->expects($this->once())
                ->method('addRelatedEntity');

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $bt]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->setRelated('foo', null);
    }

    public function testSetRelatedIsShareRelation(): void
    {
        $so = $this->getMockBuilder(ShareOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $so]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(RuntimeException::class);
        $e->setRelated('foo', null);
    }

    public function testSetRelatedRelationIsNotBelongsToButEntityIsNull(): void
    {
        $ho = $this->getMockBuilder(HasOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $ho]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(RuntimeException::class);
        $e->setRelated('foo', null);
    }

    public function testSetLinkRelationNotFound(): void
    {
        $eMapper = $this->getEntityMapper([
            'getRelations' => []
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(RelationNotFoundException::class);
        $e->link('foo', $this->getEntityInstance());
    }

    public function testSetLinkRelationIsNotShareRelation(): void
    {
        $ho = $this->getMockBuilder(HasOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $ho]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(RuntimeException::class);
        $e->link('foo', $this->getEntityInstance());
    }

    public function testSetLinkSuccess(): void
    {
        $this->setLinkTests('link', true);
        $this->setLinkTests('unlink', false);
    }

    public function testGetRelatedUsingLoaderCache(): void
    {
        $related = 123;

        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $bt->expects($this->any())
                ->method('getResult')
                ->will($this->returnValue($related));

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $bt]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = ['foo' => $bt];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals($related, $e->getRelated('foo'));
        $this->assertEquals($related, $e->getRelated('bar:foo'));
        $this->assertEquals($related, $e->getRelated('bar:foo'));
    }


    public function testGetColumnRecordIsDeleted(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->markAsDeleted();

        $this->expectException(EntityStateException::class);
        $e->getColumn('foo');
    }

    public function testGetColumnNotExists(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(PropertyNotFoundException::class);
        $e->getColumn('foo');
    }

    public function testGetColumnUsingCasts(): void
    {
        $eMapper = $this->getEntityMapper([
            'getCasts' => ['foo' => 'int']
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => '23',
            'id' => 1
        ];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals(23, $e->getColumn('foo'));
    }

    public function testGetColumnUsingGetters(): void
    {
        $eMapper = $this->getEntityMapper([
            'getGetters' => [
                'foo' => function ($name) {
                            return strtolower($name);
                }
             ]
        ], []);

        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'TNH',
            'id' => 1
        ];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals('tnh', $e->getColumn('foo'));
    }

    public function testGetColumnNeedRefresh(): void
    {
        $dbData = [
            'id' => 1,
            'foo' => 'bar',
        ];

        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('get')
                ->will($this->returnValue($dbData));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('query')
                ->will($this->returnValue($rs));

        $eMapper = $this->getEntityMapper(['getTable' => 'my_table'], []);

        $eManager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertFalse($e->hasColumn('foo'));

        $e->refresh();

        $this->assertEquals('bar', $e->getColumn('foo'));
        $this->assertEquals(1, $e->getColumn('id'));
    }


    public function testGetColumnIsPrimaryKey(): void
    {
        $primaryKey = $this->getMockBuilder(PrimaryKey::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $primaryKey->expects($this->any())
                ->method('__toString')
                ->will($this->returnValue('pk_id'));

        $eMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey
        ], []);

        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'TNH',
            'pk_id' => 1
        ];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals(1, $e->getColumn('pk_id'));
    }

    public function testSetColumnRecordIsDeleted(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->markAsDeleted();

        $this->expectException(EntityStateException::class);
        $e->setColumn('foo', 'bar');
    }

    public function testSetColumnRecordIsReadOnly(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = true;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(EntityStateException::class);
        $e->setColumn('foo', 'bar');
    }

    public function testSetColumnUsingSetters(): void
    {
        $eMapper = $this->getEntityMapper([
            'getSetters' => [
                'foo' => function ($value) {
                            return strtolower($value);
                }
             ]
        ], []);

        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->setColumn('foo', 'TNH');

        $this->assertEquals('tnh', $e->getColumn('foo'));
    }

    public function testSetColumnUsingCasts(): void
    {
        $eMapper = $this->getEntityMapper([
            'getCasts' => ['foo' => 'int']
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );
        $e->setColumn('foo', '23');
        $this->assertEquals(23, $e->getColumn('foo'));
    }

    public function testSetColumnNeedRefresh(): void
    {
        $dbData = [
            'id' => 1,
            'foo' => 'bar',
        ];

        $rs = $this->getMockBuilder(ResultSet::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $rs->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnSelf());

        $rs->expects($this->any())
                ->method('get')
                ->will($this->returnValue($dbData));

        $cnx = $this->getMockBuilder(Connection::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $cnx->expects($this->any())
                ->method('query')
                ->will($this->returnValue($rs));

        $eMapper = $this->getEntityMapper(['getTable' => 'my_table'], []);

        $eManager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);

        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertFalse($e->hasColumn('foo'));

        $e->refresh();

        $e->setColumn('foo', 123);

        $this->assertEquals(123, $e->getColumn('foo'));
        $this->assertEquals(1, $e->getColumn('id'));
    }

    public function testClearColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );
        $e->setColumn('foo', 23);
        $this->assertEquals(23, $e->getColumn('foo'));

        $e->clearColumn('foo');
        //Only columns data is cleared not raw columns
        $this->assertEquals(23, $e->getColumn('foo'));
    }

    public function testFillSuccess(): void
    {
        $fillable = [];
        $guarded = [];

        $eMapper = $this->getEntityMapper([
            'getFillable' => $fillable,
            'getGuarded' => $guarded,
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $data = [
            'foo' => 'bar'
        ];

        $this->assertFalse($e->hasColumn('foo'));
        $e->fill($data);
        $this->assertTrue($e->hasColumn('foo'));
    }

    public function testFillOnlyFillable(): void
    {
        $fillable = ['foo'];
        $guarded = [];

        $eMapper = $this->getEntityMapper([
            'getFillable' => $fillable,
            'getGuarded' => $guarded,
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $data = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $this->assertFalse($e->hasColumn('foo'));
        $this->assertFalse($e->hasColumn('bar'));
        $e->fill($data);
        $this->assertTrue($e->hasColumn('foo'));
        $this->assertFalse($e->hasColumn('bar'));
    }

    public function testFillGuarded(): void
    {
        $fillable = [];
        $guarded = ['foo'];

        $eMapper = $this->getEntityMapper([
            'getFillable' => $fillable,
            'getGuarded' => $guarded,
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $data = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $this->assertFalse($e->hasColumn('foo'));
        $this->assertFalse($e->hasColumn('bar'));
        $e->fill($data);
        $this->assertFalse($e->hasColumn('foo'));
        $this->assertTrue($e->hasColumn('bar'));
    }

    public function testClearRelated(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $bt]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertTrue($e->hasRelation('foo'));
        $e->clearRelated('foo', true);
        $reflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'relations');
        $this->assertEmpty($reflection->getValue($e));
    }


    public function testClearRelatedUsingCacheKey(): void
    {
        $bt = $this->getMockBuilder(BelongsTo::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $bt]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertTrue($e->hasRelation('foo'));
        $e->clearRelated('bar:foo', true);
        $reflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'relations');
        $this->assertEmpty($reflection->getValue($e));
    }

    public function testMarkAsSavedNotComposite(): void
    {
        $this->markAsSavedCompositeOrNotTests(false, 10);
    }

    public function testMarkAsSavedNotCompositeUsingLink(): void
    {
        $this->markAsSavedCompositeOrNotTests(false, 10, true);
    }

    public function testMarkAsSavedComposite(): void
    {
        $this->markAsSavedCompositeOrNotTests(true, ['id' => 5]);
    }

    public function testMarkAsUpdatedUsingLink(): void
    {
        $this->markAsUpdatedTests('2020-01-01', true);
    }

    public function testMarkAsUpdated(): void
    {
        $this->markAsUpdatedTests('2020-01-01', false);
    }

    public function testSetRawColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );
        $e->setRawColumn('foo', 23);
        $this->assertEquals(23, $e->getColumn('foo'));
    }

    public function testHydrateDataStoreReturnFalse(): void
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

        $eMapper = $this->getEntityMapper(['getTable' => 'my_table'], []);

        $eManager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertFalse($e->isDeleted());

        $e->refresh();

        $this->runPrivateProtectedMethod($e, 'hydrate');

        $this->assertTrue($e->isDeleted());
    }

    public function testHydratePrimaryKeyIsSet(): void
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
                ->method('getValue')
                ->will($this->returnValue(['id' => 1]));

        $eMapper = $this->getEntityMapper([
            'getPrimaryKey' => $primaryKey,
            'getTable' => 'my_table'
        ], []);

        $eManager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertFalse($e->isDeleted());

        $e->refresh();

        $this->runPrivateProtectedMethod($e, 'hydrate');

        $this->assertTrue($e->isDeleted());
    }

    public function testHydrateNoNeed(): void
    {

        $eMapper = $this->getEntityMapper([], []);

        $eManager = $this->getEntityManager([], []);
        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertFalse($e->isDeleted());

        $this->runPrivateProtectedMethod($e, 'hydrate');

        $this->assertFalse($e->isDeleted());
    }

    /**
     * @dataProvider getCastsGetDataProvider
     *
     * @param mixed $value
     * @param string $type
     * @param mixed $expected
     * @param bool $exception
     * @return void
     */
    public function testCastsGet($value, string $type, $expected, bool $exception = false): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([
            'getDateFormat' => 'Y-m-d H:i:s'
        ], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        if ($exception) {
            $this->expectException(RuntimeException::class);
        }
        $result = $this->runPrivateProtectedMethod($e, 'castGet', [$value, $type]);

        $this->assertEquals($result, $expected);
    }

    /**
     * @dataProvider getCastsSetDataProvider
     *
     * @param mixed $value
     * @param string $type
     * @param mixed $expected
     * @param bool $exception
     * @return void
     */
    public function testCastsSet($value, string $type, $expected, bool $exception = false): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([
            'getDateFormat' => 'Y-m-d H:i:s'
        ], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        if ($exception) {
            $this->expectException(RuntimeException::class);
        }
        $result = $this->runPrivateProtectedMethod($e, 'castSet', [$value, $type]);

        $this->assertEquals($result, $expected);
    }

    /**
     * Data provider for "testCastsGet"
     * @return array
     */
    public function getCastsGetDataProvider(): array
    {
        $date = new DateTime();
        $date->setDate(2020, 10, 10);
        $date->setTime(10, 50, 10);

        $o = new stdClass();
        $o->foo = 'bar';

        return array(
            array('1', 'unknown type', 1, true),
            array('1', 'int', 1),
            array('1123', 'integer', 1123),
            array('11.23', 'float', 11.23),
            array('11.2', 'double', 11.2),
            array(null, '?integer', null),
            array(1, '?integer', 1),
            array(0, 'bool', false),
            array(1, 'boolean', true),
            array(1, 'string', '1'),
            array('{"foo":"bar"}', 'json', $o),
            array('{"foo":"bar"}', 'json-assoc', ['foo' => 'bar']),
            array('2020-10-10 10:50:10', 'date', $date),
        );
    }

    /**
     * Data provider for "testCastsSet"
     * @return array
     */
    public function getCastsSetDataProvider(): array
    {
        $date = new DateTime();
        $date->setDate(2020, 10, 10);
        $date->setTime(10, 50, 10);
        return array(
            array('1', 'unknown type', 1, true),
            array('1', 'int', 1),
            array('1123', 'integer', 1123),
            array('11.23', 'float', 11.23),
            array('11.2', 'double', 11.2),
            array(null, '?integer', null),
            array(1, '?integer', 1),
            array(0, 'bool', false),
            array(1, 'boolean', true),
            array(1, 'string', '1'),
            array(['foo' => 'bar'], 'json', '{"foo":"bar"}'),
            array(['foo' => 'bar'], 'json-assoc', '{"foo":"bar"}'),
            array($date, 'date', '2020-10-10 10:50:10'),

        );
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

    private function setLinkTests(string $method, bool $linkOrNot): void
    {
        $so = $this->getMockBuilder(ShareOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $so]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->{$method}('foo', $this->getEntityInstance());
        $reflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'pendingLinks');
        $infos = $reflection->getValue($e);
        $this->assertCount(1, $infos);
        $this->assertIsArray($infos[0]);
        $this->assertArrayHasKey('relation', $infos[0]);
        $this->assertArrayHasKey('entity', $infos[0]);
        $this->assertArrayHasKey('link', $infos[0]);

        $this->assertInstanceOf(ShareOne::class, $infos[0]['relation']);
        $this->assertInstanceOf(Entity::class, $infos[0]['entity']);
        $this->assertEquals($linkOrNot, $infos[0]['link']);
    }

    private function markAsSavedCompositeOrNotTests(bool $composite, $id, bool $useLink = false): void
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
                ->method('isComposite')
                ->will($this->returnValue($composite));

        $primaryKey->expects($this->any())
                ->method('columns')
                ->will($this->returnValue(['id']));

        $emMock = [
            'getPrimaryKey' => $primaryKey
        ];
        if ($useLink) {
             $so = $this->getMockBuilder(ShareOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();
             $emMock['getRelations'] = ['foo' => $so];
        }

        $eMapper = $this->getEntityMapper($emMock, []);



        $eManager = $this->getEntityManager([
            'getConnection' => $cnx
        ], []);

        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertTrue($e->isNew());
        $this->assertEmpty($e->getRawColumns());
        $reflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'refresh');
        $this->assertFalse($reflection->getValue($e));

        $e->setColumn('foo', 123);
        $this->assertCount(1, $e->getModifiedColumns());

        $plReflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'pendingLinks');
        if ($useLink) {
            $e->link('foo', $this->getEntityInstance());
            $infos = $plReflection->getValue($e);
            $this->assertCount(1, $infos);
        }

        $this->assertTrue($e->markAsSaved($id));

        if ($useLink) {
            $infos = $plReflection->getValue($e);
            $this->assertCount(0, $infos);
        }

        $this->assertFalse($e->isNew());
        $this->assertEmpty($e->getModifiedColumns());
        $this->assertCount(2, $e->getRawColumns());
        $this->assertTrue($reflection->getValue($e));

        //hydrate return false so will mark entity as deleted
        $this->expectException(EntityStateException::class);
        $e->getColumn('id');
    }

    private function markAsUpdatedTests(string $updatedAt, bool $useLink = false): void
    {

        $emMock = [
            'getTimestampColumns' => ['ccol' , 'ucol']
        ];
        if ($useLink) {
             $so = $this->getMockBuilder(ShareOne::class)
                            ->disableOriginalConstructor()
                            ->getMock();
             $emMock['getRelations'] = ['foo' => $so];
        }

        $eMapper = $this->getEntityMapper($emMock, []);

        $eManager = $this->getEntityManager([], []);

        $columns = [];

        $loaders = [];
        $readOnly = false;
        $isNew = true;

        $e = new DataMapper(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEmpty($e->getRawColumns());

        $e->setColumn('foo', 123);
        $this->assertCount(1, $e->getModifiedColumns());

        $plReflection = $this->getPrivateProtectedAttribute(DataMapper::class, 'pendingLinks');
        if ($useLink) {
            $e->unlink('foo', $this->getEntityInstance());
            $infos = $plReflection->getValue($e);
            $this->assertCount(1, $infos);
        }

        $this->assertTrue($e->markAsUpdated($updatedAt));

        if ($useLink) {
            $infos = $plReflection->getValue($e);
            $this->assertCount(0, $infos);
        }

        $this->assertEmpty($e->getModifiedColumns());
        $this->assertCount(2, $e->getRawColumns());

        $this->assertEquals($updatedAt, $e->getColumn('ucol'));
    }
}
