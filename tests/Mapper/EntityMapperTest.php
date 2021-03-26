<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Mapper;

use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Query\Query;
use Platine\Orm\Relation\BelongsTo;
use Platine\Orm\Relation\ForeignKey;
use Platine\Orm\Relation\HasMany;
use Platine\Orm\Relation\HasOne;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\ShareMany;
use Platine\Orm\Relation\ShareOne;
use Platine\PlatineTestCase;

/**
 * EntityMapper class tests
 *
 * @group core
 * @group database
 */
class EntityMapperTest extends PlatineTestCase
{
    public function testDefaultValues(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $this->assertEquals($entityClass, $e->getEntityClass());
        $this->assertEquals('foos', $e->getTable());
        $this->assertEquals('foo', $e->getName());
        $this->assertEquals('foos_id_seq', $e->getSequence());

        $primaryKey = $e->getPrimaryKey();
        $pkColumns = $primaryKey->columns();
        $this->assertInstanceOf(PrimaryKey::class, $primaryKey);
        $this->assertCount(1, $pkColumns);
        $this->assertEquals('id', $pkColumns[0]);

        $foreignKey = $e->getForeignKey();
        $fkColumns = $foreignKey->columns();
        $this->assertInstanceOf(ForeignKey::class, $foreignKey);
        $this->assertCount(1, $fkColumns);
        $this->assertArrayHasKey('id', $fkColumns);
        $this->assertEquals('foo_id', $fkColumns['id']);

        $this->assertNull($e->getPrimaryKeyGenerator());

        $this->assertEmpty($e->getGetters());
        $this->assertEmpty($e->getSetters());
        $this->assertEmpty($e->getCasts());
        $this->assertEmpty($e->getRelations());
        $this->assertEmpty($e->getFilters());
        $this->assertEmpty($e->getFillable());
        $this->assertEmpty($e->getGuarded());
        $this->assertEmpty($e->getEventHandlers());

        $this->assertFalse($e->hasSoftDelete());
        $this->assertEquals('deleted_at', $e->getSoftDeleteColumn());

        $this->assertFalse($e->hasTimestamp());
        $timestampColumns = $e->getTimestampColumns();
        $this->assertCount(2, $timestampColumns);
        $this->assertEquals('created_at', $timestampColumns[0]);
        $this->assertEquals('updated_at', $timestampColumns[1]);
    }

    public function testName(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = 'my_entity_name';
        $e->name($value);
        $this->assertEquals($value, $e->getName());

        $e->name('');
        $this->assertEquals('foo', $e->getName());
    }

    public function testTable(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = 'my_table_name';
        $e->table($value);
        $this->assertEquals($value, $e->getTable());
    }

    public function testSequence(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = 'my_sequence_name';
        $e->sequence($value);
        $this->assertEquals($value, $e->getSequence());

        $e->sequence('');
        $this->assertEmpty($e->getSequence());
    }

    public function testPrimaryKey(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        //Non-composite
        $pk = 'my_pk_name';
        $e->primaryKey($pk);

        $primaryKey = $e->getPrimaryKey();
        $pkColumns = $primaryKey->columns();
        $this->assertInstanceOf(PrimaryKey::class, $primaryKey);
        $this->assertCount(1, $pkColumns);
        $this->assertEquals($pk, $pkColumns[0]);

        //composite
        $e->primaryKey('first_pk', 'sec_pk');

        $primaryKeyComp = $e->getPrimaryKey();
        $pkColumnsComp = $primaryKeyComp->columns();
        $this->assertInstanceOf(PrimaryKey::class, $primaryKeyComp);
        $this->assertCount(2, $pkColumnsComp);
        $this->assertEquals('first_pk', $pkColumnsComp[0]);
        $this->assertEquals('sec_pk', $pkColumnsComp[1]);
    }

    public function testPrimaryKeyGenerator(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = function () {
            return 12;
        };

        $e->primaryKeyGenerator($value);
        $this->assertEquals($value, $e->getPrimaryKeyGenerator());
        $this->assertEquals(12, ($e->getPrimaryKeyGenerator())());
    }

    public function testGetters(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = function ($name) {
            return strtolower($name);
        };
        $column = 'name';
        $e->getter($column, $value);

        $result = $e->getGetters();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($column, $result);
        $this->assertEquals($value, $result[$column]);
    }

    public function testSetters(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = function ($name) {
            return strtolower($name);
        };
        $column = 'name';
        $e->setter($column, $value);

        $result = $e->getSetters();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($column, $result);
        $this->assertEquals($value, $result[$column]);
    }

    public function testCasts(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $column = 'name';
        $value = [$column => 'int'];

        $e->casts($value);

        $result = $e->getCasts();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($column, $result);
        $this->assertEquals('int', $result[$column]);
    }

    /**
     * @dataProvider getRelationsDataProvider
     *
     * @param string $method
     * @param string $relationClass
     * @return void
     */
    public function testRelations(string $method, string $relationClass): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $value = 'foo';
        $related = 'My\Namespace\Bar';

        $e->relation($value)->{$method}($related);

        $result = $e->getRelations();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($value, $result);
        $this->assertInstanceOf($relationClass, $result[$value]);
    }

    public function testFillable(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $column = 'name';
        $value = [$column];

        $e->fillable($value);

        $result = $e->getFillable();
        $this->assertCount(1, $result);
        $this->assertContains($column, $result);
        $this->assertEquals($column, $result[0]);
    }

    public function testGuarded(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $column = 'name';
        $value = [$column];

        $e->guarded($value);

        $result = $e->getGuarded();
        $this->assertCount(1, $result);
        $this->assertContains($column, $result);
        $this->assertEquals($column, $result[0]);
    }

    public function testFilters(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $filter = 'active_users';
        $value = function (Query $q) {
            $q->where('baz')->is(1);
        };

        $e->filter($filter, $value);

        $result = $e->getFilters();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($filter, $result);
        $this->assertEquals($value, $result[$filter]);
    }

    public function testSoftDelete(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $column = 'deleted';

        $e->useSoftDelete(true, $column);
        $e->casts([$column => '?date']);

        $this->assertTrue($e->hasSoftDelete());
        $this->assertEquals($column, $e->getSoftDeleteColumn());
    }

    public function testTimestamp(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $column1 = 'created';
        $column2 = 'updated';

        $e->useTimestamp(true, $column1, $column2);
        $e->casts([
            $column1 => 'date',
            $column2 => '?date',
        ]);

        $this->assertTrue($e->hasTimestamp());
        $columns = $e->getTimestampColumns();
        $this->assertCount(2, $columns);
        $this->assertEquals($column1, $columns[0]);
        $this->assertEquals($column2, $columns[1]);
    }

    public function testEventHandlers(): void
    {
        $entityClass = 'My\Namespace\Foo';
        $e = new EntityMapper($entityClass);

        $e->on('foo_event', function () {
        });
        $e->on('bar_event', function () {
        });
        $e->on('foo_event', function () {
        });

        $result = $e->getEventHandlers();
        $this->assertArrayHasKey('foo_event', $result);
        $this->assertArrayHasKey('bar_event', $result);
        $this->assertCount(1, $result['bar_event']);
        $this->assertCount(2, $result['foo_event']);
    }

    /**
     * Data provider for "testRelations"
     * @return array
     */
    public function getRelationsDataProvider(): array
    {
        return array(
            array('belongsTo', BelongsTo::class),
            array('hasOne', HasOne::class),
            array('hasMany', HasMany::class),
            array('shareOne', ShareOne::class),
            array('shareMany', ShareMany::class)
        );
    }
}
