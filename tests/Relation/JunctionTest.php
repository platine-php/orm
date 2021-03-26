<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Relation;

use Platine\Orm\Relation\Junction;
use Platine\PlatineTestCase;

/**
 * Junction class tests
 *
 * @group core
 * @group database
 */
class JunctionTest extends PlatineTestCase
{
    public function testAll(): void
    {

        $e = new Junction('foo', ['id']);

        $columns = $e->columns();
        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns[0]);
        $this->assertEquals('foo', $e->table());
    }
}
