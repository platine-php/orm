<?php

declare(strict_types=1);

namespace Platine\Test\Orm;

use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Platine\Orm\Query\EntityQuery;
use Platine\Dev\PlatineTestCase;
use Platine\Test\Fixture\Orm\Connection;
use RuntimeException;
use stdClass;

/**
 * EntityManager class tests
 *
 * @group core
 * @group database
 */
class EntityManagerTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $cnx = new Connection('MySQL');
        $e = new EntityManager($cnx);

        $this->assertInstanceOf(Connection::class, $e->getConnection());
        $this->assertEquals('Y-m-d H:i:s', $e->getDateFormat());
    }

    public function testQuery(): void
    {
        $cnx = new Connection('MySQL');
        $e = new EntityManager($cnx);

        $entity = $this->getEntityInstance();
        $q = $e->query(get_class($entity));
        $this->assertInstanceOf(EntityQuery::class, $q);
    }

    public function testGetEntityMapperClassNotFound(): void
    {
        $cnx = new Connection('MySQL');
        $e = new EntityManager($cnx);

        $this->expectException(RuntimeException::class);
        $e->getEntityMapper('foo_class_not_found');
    }

    public function testGetEntityMapperClassNotExtendEntity(): void
    {
        $cnx = new Connection('MySQL');
        $e = new EntityManager($cnx);

        $this->expectException(RuntimeException::class);
        $e->getEntityMapper(stdClass::class);
    }

    public function testGetEntityMapperSuccess(): void
    {
        $cnx = new Connection('MySQL');
        $e = new EntityManager($cnx);

        $entity = $this->getEntityInstance();

        $q = $e->getEntityMapper(get_class($entity));
        $this->assertInstanceOf(EntityMapper::class, $q);

        //Already cached
        $qc = $e->getEntityMapper(get_class($entity));
        $this->assertEquals($q, $qc);
    }


    private function getEntityInstance(): Entity
    {
        $em = $this->getMockBuilder(EntityManager::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $dm = $this->getMockBuilder(EntityMapper::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        return new class ($em, $dm) extends Entity
        {

            public static function mapEntity(EntityMapperInterface $mapper): void
            {
            }
        };
    }
}
