<?php

declare(strict_types=1);

namespace Platine\Test\Orm\Query;

use Closure;
use Platine\Database\Query\ColumnExpression;
use Platine\Database\Query\HavingStatement;
use Platine\Database\Query\QueryStatement;
use Platine\Orm\Query\Query;
use Platine\Dev\PlatineTestCase;

/**
 * Query class tests
 *
 * @group core
 * @group database
 */
class QueryTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $qs = $this->getQueryStatementInstance();
        $e = new Query($qs);

        $hsr = $this->getPrivateProtectedAttribute(Query::class, 'havingStatement');

        $this->assertInstanceOf(QueryStatement::class, $e->getQueryStatement());
        $this->assertInstanceOf(HavingStatement::class, $this->runPrivateProtectedMethod($e, 'getHavingStatement'));
    }

    public function testClone(): void
    {
        $qs = $this->getQueryStatementInstance();
        $e = new Query($qs);

        $c = clone $e;

        $hsr = $this->getPrivateProtectedAttribute(Query::class, 'havingStatement');

        $this->assertInstanceOf(HavingStatement::class, $hsr->getValue($c));
    }

    public function testSoftDeleted(): void
    {
        $qs = $this->getQueryStatementInstance();
        $e = new Query($qs);

        $sdr = $this->getPrivateProtectedAttribute(Query::class, 'withSoftDeleted');
        $osdr = $this->getPrivateProtectedAttribute(Query::class, 'onlySoftDeleted');

        $this->assertFalse($sdr->getValue($e));
        $this->assertFalse($osdr->getValue($e));

        $e->withDeleted(true);
        $this->assertTrue($sdr->getValue($e));
        $this->assertFalse($osdr->getValue($e));

        $e->onlyDeleted(true);

        $this->assertTrue($sdr->getValue($e));
        $this->assertTrue($osdr->getValue($e));
    }

    public function testWith(): void
    {
        $qs = $this->getQueryStatementInstance();
        $e = new Query($qs);

        $wr = $this->getPrivateProtectedAttribute(Query::class, 'with');
        $ir = $this->getPrivateProtectedAttribute(Query::class, 'immediate');

        $this->assertEmpty($wr->getValue($e));
        $this->assertFalse($ir->getValue($e));

        //Value is string
        $value = 'my_relation';
        $e->with($value);

        $this->assertCount(1, $wr->getValue($e));
        $this->assertContains($value, $wr->getValue($e));
        $this->assertFalse($ir->getValue($e));

        //Value is array
        $valueArr = 'my_relation';
        $e->with([$valueArr], true);

        $this->assertCount(1, $wr->getValue($e));
        $this->assertContains($valueArr, $wr->getValue($e));
        $this->assertTrue($ir->getValue($e));
    }

    public function testDistinct(): void
    {
        $qs = $this->getQueryStatementInstance([], ['setDistinct', 'hasDistinct']);
        $e = new Query($qs);

        $this->assertFalse($e->getQueryStatement()->hasDistinct());

        $e->distinct(true);


        $this->assertTrue($e->getQueryStatement()->hasDistinct());
    }

    public function testGroupBy(): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'addGroupBy',
            'getGroupBy',
            'closureToExpression'
        ]);

        $e = new Query($qs);

        $this->assertEmpty($e->getQueryStatement()->getGroupBy());

        $name = 'foo';
        $e->groupBy($name);

        $result = $e->getQueryStatement()->getGroupBy();
        $this->assertCount(1, $result);
        $this->assertContains($name, $result);
    }

    public function testOrderBy(): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'addOrder',
            'getOrder',
            'closureToExpression'
        ]);

        $e = new Query($qs);

        $this->assertEmpty($e->getQueryStatement()->getOrder());

        $name = 'foo';
        $e->orderBy($name);

        $result = $e->getQueryStatement()->getOrder();
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('order', $result[0]);
        $this->assertArrayHasKey('columns', $result[0]);
    }

    public function testLimitOffset(): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'setLimit',
            'setOffset',
            'getLimit',
            'getOffset'
        ]);
        $e = new Query($qs);

        $this->assertEquals(0, $e->getQueryStatement()->getLimit());
        $this->assertEquals(-1, $e->getQueryStatement()->getOffset());

        $e->limit(10);
        $e->offset(1);


        $this->assertEquals(10, $e->getQueryStatement()->getLimit());
        $this->assertEquals(1, $e->getQueryStatement()->getOffset());
    }

    public function testSelectColumnIsString(): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'addColumn',
            'getColumns',
            'closureToExpression',
        ]);
        $e = new Query($qs);

        $column = 'foo';
        $this->runPrivateProtectedMethod($e, 'select', [$column]);

        $result = $e->getQueryStatement()->getColumns();
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('alias', $result[0]);
        $this->assertEquals($column, $result[0]['name']);
    }

    public function testSelectColumnIsClosure(): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'addColumn',
            'getColumns',
            'closureToExpression',
        ]);
        $e = new Query($qs);

        $column = function (ColumnExpression $exp) {
            $exp->column('foo');
        };

        $this->runPrivateProtectedMethod($e, 'select', [$column]);

        $result = $e->getQueryStatement()->getColumns();
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('alias', $result[0]);
        $this->assertEquals('foo', $result[0]['name']);
    }

    public function testGetWithAttributesEmptyData(): void
    {
        $qs = $this->getQueryStatementInstance([], []);
        $e = new Query($qs);

        $result = $this->runPrivateProtectedMethod($e, 'getWithAttributes');

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('with', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertEmpty($result['with']);
        $this->assertEmpty($result['extra']);
    }

    public function testGetWithAttributesSimple(): void
    {
        $qs = $this->getQueryStatementInstance([], []);
        $e = new Query($qs);

        $e->with('foo');

        $result = $this->runPrivateProtectedMethod($e, 'getWithAttributes');

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('with', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertCount(1, $result['with']);
        $this->assertCount(1, $result['extra']);
        $this->assertArrayHasKey('foo', $result['with']);
        $this->assertArrayHasKey('foo', $result['extra']);
        $this->assertNull($result['with']['foo']);
    }

    public function testGetWithAttributesCallable(): void
    {
        $qs = $this->getQueryStatementInstance([], []);
        $e = new Query($qs);

        $e->with(['foo.type' => function () {
        }]);

        $result = $this->runPrivateProtectedMethod($e, 'getWithAttributes');

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('with', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertCount(1, $result['with']);
        $this->assertCount(1, $result['extra']);
        $this->assertCount(1, $result['extra']['foo']);
        $this->assertArrayHasKey('foo', $result['with']);
        $this->assertArrayHasKey('foo', $result['extra']);
        $this->assertInstanceOf(Closure::class, $result['extra']['foo']['type']);
    }

    public function testGetWithAttributesMultiplePath(): void
    {
        $qs = $this->getQueryStatementInstance([], []);
        $e = new Query($qs);

        $e->with(['bar', 'bar.foo', 'bar.foo']);

        $result = $this->runPrivateProtectedMethod($e, 'getWithAttributes');

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('with', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertCount(1, $result['with']);
        $this->assertCount(1, $result['extra']);
        $this->assertArrayHasKey('bar', $result['with']);
        $this->assertArrayHasKey('bar', $result['extra']);
        $this->assertNull($result['with']['bar']);
        $this->assertEquals('foo', $result['extra']['bar'][0]);
    }

    public function testHaving(): void
    {
        $this->havingTesting('having', 'AND');
        $this->havingTesting('orHaving', 'OR');
    }

    private function havingTesting(string $method, $separator): void
    {
        $qs = $this->getQueryStatementInstance([], [
            'addHavingGroup',
            'getHaving',
            'closureToExpression'
        ]);

        $e = new Query($qs);

        $hsr = $this->getPrivateProtectedAttribute(Query::class, 'havingStatement');

        /** @var HavingStatement $have */
        $have = $hsr->getValue($e);

        $this->assertEmpty($have->getQueryStatement()->getHaving());

        $name = function () {
        };
        $e->{$method}($name);

        /** @var HavingStatement $have */
        $have = $hsr->getValue($e);

        $result = $have->getQueryStatement()->getHaving();
        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('conditions', $result[0]);
        $this->assertArrayHasKey('separator', $result[0]);
        $this->assertEquals($separator, $result[0]['separator']);
    }

    private function getQueryStatementInstance(array $mockInfos = [], array $excludeModck = []): QueryStatement
    {
        $excludeMethods = $this->getClassMethodsToMock(QueryStatement::class, $excludeModck);

        /** @var QueryStatement $qs */
        $qs = $this->getMockBuilder(QueryStatement::class)
                    ->onlyMethods($excludeMethods)
                    ->getMock();

        foreach ($mockInfos as $method => $returnValue) {
            $qs->expects($this->any())
                ->method($method)
                ->will($this->returnValue($returnValue));
        }

        return $qs;
    }
}
