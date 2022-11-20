<?php

/**
 * Platine ORM
 *
 * Platine ORM provides a flexible and powerful ORM implementing a data-mapper pattern.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine ORM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 *  @file Query.php
 *
 *  The Query class
 *
 *  @package    Platine\Orm\Query
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm\Query;

use Closure;
use Platine\Database\Query\BaseStatement;
use Platine\Database\Query\ColumnExpression;
use Platine\Database\Query\Expression;
use Platine\Database\Query\HavingStatement;
use Platine\Database\Query\QueryStatement;

/**
 * Class Query
 * @package Platine\Orm\Query
 */
class Query extends BaseStatement
{
    /**
     *
     * @var HavingStatement
     */
    protected HavingStatement $havingStatement;

    /**
     * The list of relation data to load with the query
     * @var array<int, string>|array<string, Closure>
     */
    protected array $with = [];

    /**
     * Whether need load relation data immediately
     * @var bool
     */
    protected bool $immediate = false;

    /**
     * Whether return the data with the deleted
     * @var bool
     */
    protected bool $withSoftDeleted = false;

    /**
     * Whether to return only deleted data
     * @var bool
     */
    protected bool $onlySoftDeleted = false;


    /**
     *
     * @param QueryStatement|null $queryStatement
     */
    public function __construct(QueryStatement $queryStatement = null)
    {
        parent::__construct($queryStatement);
        $this->havingStatement = new HavingStatement($this->queryStatement);
    }

    /**
     * Clone the object
     */
    public function __clone()
    {
        parent::__clone();
        $this->havingStatement = new HavingStatement($this->queryStatement);
    }

    /**
     *
     * @param bool $value
     * @return $this
     */
    public function withDeleted(bool $value = true): self
    {
        $this->withSoftDeleted = $value;

        return $this;
    }

    /**
     *
     * @param bool $value
     * @return $this
     */
    public function onlyDeleted(bool $value = true): self
    {
        $this->onlySoftDeleted = $this->withSoftDeleted = $value;

        return $this;
    }

    /**
     *
     * @param string|array<int, string>|array<string, Closure> $value
     * @param bool $immediate
     * @return $this
     */
    public function with($value, bool $immediate = false): self
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $this->with = $value;
        $this->immediate = $immediate;

        return $this;
    }

    /**
     *
     * @param bool $distinct
     * @return $this
     */
    public function distinct(bool $distinct = true): self
    {
        $this->getQueryStatement()->setDistinct($distinct);

        return $this;
    }

    /**
     *
     * @param string|Expression|Closure|string[]|Expression[]|Closure[] $columns
     * @return $this
     */
    public function groupBy($columns): self
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $this->getQueryStatement()->addGroupBy($columns);

        return $this;
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param Closure $value
     * @return $this
     */
    public function having($column, Closure $value = null): self
    {
        $this->getHavingStatement()->having($column, $value);

        return $this;
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param Closure $value
     * @return $this
     */
    public function orHaving($column, Closure $value = null): self
    {
        $this->getHavingStatement()->orHaving($column, $value);

        return $this;
    }

    /**
     *
     * @param string|Closure|Expression|string[]|Expression[]|Closure[] $columns
     * @param string $order
     * @return $this
     */
    public function orderBy($columns, string $order = 'ASC'): self
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $this->getQueryStatement()->addOrder($columns, $order);

        return $this;
    }

    /**
     *
     * @param int $value
     * @return $this
     */
    public function limit(int $value): self
    {
        $this->getQueryStatement()->setLimit($value);

        return $this;
    }

    /**
     *
     * @param int $value
     * @return $this
     */
    public function offset(int $value): self
    {
        $this->getQueryStatement()->setOffset($value);

        return $this;
    }

    /**
     *
     * @return HavingStatement
     */
    protected function getHavingStatement(): HavingStatement
    {
        return $this->havingStatement;
    }

    /**
     *
     * @param string|string[]|Expression|Expression[]|Closure|Closure[] $columns
     * @return void
     */
    protected function select($columns = []): void
    {
        $exp = new ColumnExpression($this->getQueryStatement());
        if ($columns instanceof Closure) {
            $columns($exp);
        } else {
            if (!is_array($columns)) {
                $columns = [$columns];
            }

            $exp->columns($columns);
        }
    }

    /**
     * Return the relation data attributes
     * @return array<string, array<string, mixed>>
     */
    protected function getWithAttributes(): array
    {
        $with = [];
        $extra = [];

        foreach ($this->with as $key => /** @var string|Closure $value */ $value) {
            $fullName = $value;
            $callback = null;

            if ($value instanceof Closure) {
                /** @var string $fullName */
                $fullName = $key;
                $callback = $value;
            }

            $fullName = explode('.', $fullName);
            $name = array_shift($fullName);
            $fullName = implode('.', $fullName);

            if ($fullName === '') {
                if (!isset($with[$name]) || $callback !== null) {
                    $with[$name] = $callback;

                    if (!isset($extra[$name])) {
                        $extra[$name] = [];
                    }
                }
            } else {
                if (!isset($extra[$name])) {
                    $with[$name] = null;
                    $extra[$name] = [];
                }

                $tmp = &$extra[$name];

                if (isset($tmp[$fullName]) || in_array($fullName, $tmp)) {
                    continue;
                }

                if ($callback === null) {
                    $tmp[] = $fullName;
                } else {
                    $tmp[$fullName] = $callback;
                }
            }
        }

        return [
            'with' => $with,
            'extra' => $extra
        ];
    }
}
