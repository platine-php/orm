<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Mapper;

use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Mapper\Proxy;
use Platine\Dev\PlatineTestCase;

/**
 * Proxy class tests
 *
 * @group core
 * @group database
 */
class ProxyTest extends PlatineTestCase
{
    public function testGetInstance(): void
    {
        $this->assertInstanceOf(Proxy::class, Proxy::instance());
    }

    public function testGetEntityColumn(): void
    {
        $eMapper = $this->getEntityMapper([], []);
        $eManager = $this->getEntityManager([], []);
        $columns = ['id' => 10];
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

        $data = Proxy::instance()->getEntityColumns($e);
        $this->assertCount(1, $data);
        $this->assertEquals(10, $data['id']);
    }

    public function testGetEntityColumnFromDataMapper(): void
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

        $data = Proxy::instance()->getEntityColumns($e);
        $this->assertEmpty($data);
    }

    public function testGetEntityDataMapper(): void
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

        $data = Proxy::instance()->getEntityDataMapper($e);
        $this->assertInstanceOf(DataMapper::class, $data);
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
