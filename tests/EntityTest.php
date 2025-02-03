<?php

declare(strict_types=1);

namespace Platine\Test\Orm;

use Platine\Dev\PlatineTestCase;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Exception\PropertyNotFoundException;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Relation\BelongsTo;
use Platine\Orm\Relation\ShareMany;
use Platine\Test\Fixture\Orm\MyEntity;

/**
 * Entity class tests
 *
 * @group core
 * @group database
 */
class EntityTest extends PlatineTestCase
{
    public function testDefaultValues(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $reflection = $this->getPrivateProtectedAttribute(Entity::class, 'dataMapperArgs');
        $dm = $reflection->getValue($e);

        $this->assertCount(6, $dm);

        $this->assertInstanceOf(EntityManager::class, $dm[0]);
        $this->assertInstanceOf(EntityMapper::class, $dm[1]);
    }

    public function testJson(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertCount(1, $e->jsonSerialize());
        $this->assertArrayHasKey('foo', $e->jsonSerialize());
        $this->assertEquals('{"foo":"bar"}', json_encode($e));
    }

    public function testJsonRealEntity(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = new MyEntity(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $er = new MyEntity(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );
        $er->name = 'foo';

        $e->foo = 'bar';
        $e->relation = $er;

        $this->assertCount(2, $e->jsonSerialize());
        $this->assertArrayHasKey('foo', $e->jsonSerialize());
        $this->assertEquals('{"foo":"bar","relation":{"name":"foo"}}', json_encode($e));
    }

    public function testDataMapperInstance(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $reflection = $this->getPrivateProtectedAttribute(Entity::class, 'dataMapper');
        $dmNull = $reflection->getValue($e);

        $this->assertNull($dmNull);

        $this->runPrivateProtectedMethod($e, 'mapper');
        $dm = $reflection->getValue($e);
        $this->assertInstanceOf(DataMapper::class, $dm);
    }

    public function testGetPropretyNotFound(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->expectException(PropertyNotFoundException::class);
        $a = $e->id;
    }

    public function testGetUsingColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals($e->foo, 'bar');
        $this->assertEquals($e->id, 1);
    }

    public function testToString(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [
            'foo' => 'bar',
            'id' => 1
        ];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals($e->__toString(), '[Platine\Orm\Entity(foo=bar, id=1)]');
    }

    public function testGetUsingRelation(): void
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
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $this->assertEquals($e->foo, $related);
    }

    public function testSetUsingNotShareRelation(): void
    {
        $related = null;

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
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->foo = null;

        $this->assertEquals($e->foo, null);
    }

    public function testSetUsingShareRelation(): void
    {
        $related = null;

        $sh = $this->getMockBuilder(ShareMany::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $sh->expects($this->any())
                ->method('getResult')
                ->will($this->returnValue($related));

        $eMapper = $this->getEntityMapper([
            'getRelations' => ['foo' => $sh]
        ], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $er = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->foo = [$er];

        $this->assertEquals($e->foo, null);
    }

    public function testIssetUsingRelation(): void
    {
        $related = null;

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
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->foo = null;

        $this->assertTrue(isset($e->foo));
    }

    public function testSetUsingColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->id = 100;

        $this->assertEquals($e->id, 100);
    }

    public function testIssetUsingColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->id = 100;

        $this->assertTrue(isset($e->id));
        $this->assertFalse(isset($e->idf));
    }

    public function testUnset(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = [];
        $loaders = [];
        $readOnly = false;
        $isNew = false;

        $e = $this->getEntityInstance(
            $eManager,
            $eMapper,
            $columns,
            $loaders,
            $readOnly,
            $isNew
        );

        $e->id = 100;

        $this->assertTrue(isset($e->id));
        unset($e->id);
        $this->assertFalse(isset($e->id));
    }

    private function getEntityInstance(
        EntityManager $em,
        EntityMapper $m,
        array $columns = [],
        array $loaders = [],
        bool $isReadOnly = false,
        bool $isNew = false
    ): Entity {
        return new class ($em, $m, $columns, $loaders, $isReadOnly, $isNew) extends Entity
        {
            public static function mapEntity(EntityMapperInterface $mapper): void
            {
            }

            public function jsonSerialize(): array
            {
                return [
                    'foo' => 'bar'
                ];
            }
        };
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
}
