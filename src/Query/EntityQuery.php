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
 *  @file EntityQuery.php
 *
 *  The EntityQuery class
 *
 *  @package    Platine\Orm\Query
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm\Query;

use Closure;
use Platine\Database\Connection;
use Platine\Database\Query\ColumnExpression;
use Platine\Database\Query\Delete;
use Platine\Database\Query\Expression;
use Platine\Database\Query\HavingStatement;
use Platine\Database\Query\QueryStatement;
use Platine\Database\Query\Update;
use Platine\Database\ResultSet;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Relation\RelationLoader;

/**
 * @class EntityQuery
 * @package Platine\Orm\Query
 * @template TEntity as Entity
 */
class EntityQuery extends Query
{
    /**
     *
     * @var EntityManager<TEntity>
     */
    protected EntityManager $manager;

    /**
     *
     * @var EntityMapper<TEntity>
     */
    protected EntityMapper $mapper;

    /**
     * Whether the query state is locked
     * @var bool
     */
    protected bool $locked = false;

    /**
     * Create new instance
     * @param EntityManager<TEntity> $manager
     * @param EntityMapper<TEntity> $mapper
     * @param QueryStatement|null $queryStatement
     */
    public function __construct(
        EntityManager $manager,
        EntityMapper $mapper,
        ?QueryStatement $queryStatement = null
    ) {
        parent::__construct($queryStatement);
        $this->manager = $manager;
        $this->mapper = $mapper;
    }

    /**
     * Return the connection instance
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->manager->getConnection();
    }

    /**
     * Apply an filter(s) to the query
     * @param string|array<int, string>|array<string, mixed> $names
     * @return $this
     */
    public function filter($names): self
    {
        if (!is_array($names)) {
            $names = [$names];
        }

        $query = new Query($this->queryStatement);
        $filters = $this->mapper->getFilters();

        foreach ($names as $name => $args) {
            if (is_int($name)) {
                $name = $args;
                $args = null;
            }

            if (isset($filters[$name])) {
                $callback = $filters[$name];
                ($callback)($query, $args);
            }
        }

        return $this;
    }

    /**
     * Return one entity record
     * @param array<int, string> $columns
     * @param bool $primaryColumn
     * @return TEntity|null
     */
    public function get(array $columns = [], bool $primaryColumn = true): ?Entity
    {
        $result = $this->query($columns, $primaryColumn)
                       ->fetchAssoc()
                       ->get();

        if ($result === false) {
            return null;
        }

        $class = $this->mapper->getEntityClass();

        return new $class(
            $this->manager,
            $this->mapper,
            $result,
            [],
            $this->isReadOnly(),
            false
        );
    }

    /**
     * Return the list of entities
     * @param array<int, string> $columns
     * @param bool $primaryColumn
     * @return TEntity[]
     */
    public function all(array $columns = [], bool $primaryColumn = true): array
    {
        $results = $this->query($columns, $primaryColumn)
                        ->fetchAssoc()
                        ->all();

        $entities = [];

        if (is_array($results)) {
            $class = $this->mapper->getEntityClass();
            $isReadOnly = $this->isReadOnly();
            $loaders = $this->getRelationLoaders($results);

            foreach ($results as $result) {
                $entities[] = new $class(
                    $this->manager,
                    $this->mapper,
                    $result,
                    $loaders,
                    $isReadOnly,
                    false
                );
            }
        }

        return $entities;
    }

    /**
     * Delete entity record
     * @param bool $force whether to bypass soft delete
     * @param array<int, string> $tables
     * @return int the affected rows
     */
    public function delete(bool $force = false, array $tables = []): int
    {
        return (int) $this->transaction(function (Connection $connection) use ($tables, $force) {
            if (!$force && $this->mapper->hasSoftDelete()) {
                return (new Update($connection, $this->mapper->getTable(), $this->queryStatement))
                        ->set([
                            $this->mapper->getSoftDeleteColumn() => date($this->manager->getDateFormat())
                        ]);
            }
            return (new Delete($connection, $this->mapper->getTable(), $this->queryStatement))->delete($tables);
        });
    }

    /**
     * Update entity record
     * @param array<int, string> $columns
     * @return int
     */
    public function update(array $columns = []): int
    {
        return (int) $this->transaction(function (Connection $connection) use ($columns) {
            if ($this->mapper->hasTimestamp()) {
                list(, $updatedAtColumn) = $this->mapper->getTimestampColumns();
                $columns[$updatedAtColumn] = date($this->manager->getDateFormat());
            }
            return (new Update($connection, $this->mapper->getTable(), $this->queryStatement))
                    ->set($columns);
        });
    }

    /**
     * Update by incremented an column
     * @param string|array<string, mixed> $column
     * @param mixed $value
     * @return int
     */
    public function increment($column, $value = 1): int
    {
        return (int) $this->transaction(function (Connection $connection) use ($column, $value) {
            if ($this->mapper->hasTimestamp()) {
                list(, $updatedAtColumn) = $this->mapper->getTimestampColumns();
                $this->queryStatement->addUpdateColumns([
                    $updatedAtColumn => date($this->manager->getDateFormat())
                ]);
            }
            return (new Update($connection, $this->mapper->getTable(), $this->queryStatement))
                    ->increment($column, $value);
        });
    }

    /**
     * Update by decremented an column
     * @param string|array<string, mixed> $column
     * @param mixed $value
     * @return int
     */
    public function decrement($column, $value = 1): int
    {
        return (int) $this->transaction(function (Connection $connection) use ($column, $value) {
            if ($this->mapper->hasTimestamp()) {
                list(, $updatedAtColumn) = $this->mapper->getTimestampColumns();
                $this->queryStatement->addUpdateColumns([
                    $updatedAtColumn => date($this->manager->getDateFormat())
                ]);
            }
            return (new Update($connection, $this->mapper->getTable(), $this->queryStatement))
                    ->decrement($column, $value);
        });
    }

    /**
     * Find entity record using primary key value
     * @param mixed $id
     *
     * @return TEntity|null
     */
    public function find($id): ?Entity
    {
        if (is_array($id)) {
            foreach ($id as $pkColumn => $pkValue) {
                $this->where($pkColumn)->is($pkValue);
            }
        } else {
            $columns = $this->mapper->getPrimaryKey()->columns();
            $this->where($columns[0])->is($id);
        }

        return $this->get();
    }

/**
     * Find entities record using primary key values
     * @param mixed ...$ids
     *
     * @return TEntity[]
     */
    public function findAll(...$ids): array
    {
        if (is_array($ids[0])) {
            $keys = array_keys($ids[0]);
            $values = [];

            foreach ($ids as $pkValue) {
                foreach ($keys as $pkColumn) {
                    $values[$pkColumn][] = $pkValue[$pkColumn];
                }
            }

            foreach ($values as $pkColumn => $pkValues) {
                $this->where($pkColumn)->in($pkValues);
            }
        } else {
            $columns = $this->mapper->getPrimaryKey()->columns();
            $this->where($columns[0])->in($ids);
        }

        return $this->all();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @return mixed
     */
    public function column($column)
    {
        (new ColumnExpression($this->queryStatement))->column($column);

        return $this->executeAggregate();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param bool $distinct
     * @return mixed
     */
    public function count($column = '*', bool $distinct = false)
    {
        (new ColumnExpression($this->queryStatement))->count($column, null, $distinct);

        return $this->executeAggregate();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param bool $distinct
     * @return mixed
     */
    public function avg($column, bool $distinct = false)
    {
        (new ColumnExpression($this->queryStatement))->avg($column, null, $distinct);

        return $this->executeAggregate();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param bool $distinct
     * @return mixed
     */
    public function sum($column, bool $distinct = false)
    {
        (new ColumnExpression($this->queryStatement))->sum($column, null, $distinct);

        return $this->executeAggregate();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param bool $distinct
     * @return mixed
     */
    public function min($column, bool $distinct = false)
    {
        (new ColumnExpression($this->queryStatement))->min($column, null, $distinct);

        return $this->executeAggregate();
    }

    /**
     *
     * @param string|Expression|Closure $column
     * @param bool $distinct
     * @return mixed
     */
    public function max($column, bool $distinct = false)
    {
        (new ColumnExpression($this->queryStatement))->max($column, null, $distinct);

        return $this->executeAggregate();
    }

    /**
     * Clone of object
     */
    public function __clone()
    {
        parent::__clone();
        $this->havingStatement = new HavingStatement($this->queryStatement);
    }

    /**
     * Build the instance of EntityQuery
     * @return $this
     */
    protected function buildQuery(): self
    {
        $this->queryStatement->addTables([$this->mapper->getTable()]);

        return $this;
    }

    /**
     * Execute query and return the result set
     * @param array<int, string> $columns
     * @param bool $primaryColumn
     * @return ResultSet
     */
    protected function query(array $columns = [], bool $primaryColumn = true): ResultSet
    {
        if (!$this->buildQuery()->locked && !empty($columns) && $primaryColumn) {
            foreach ($this->mapper->getPrimaryKey()->columns() as $pkColumn) {
                $columns[] = $pkColumn;
            }
        }

        if ($this->mapper->hasSoftDelete()) {
            if (!$this->withSoftDeleted) {
                $this->where($this->mapper->getSoftDeleteColumn())->isNull();
            } elseif ($this->onlySoftDeleted) {
                $this->where($this->mapper->getSoftDeleteColumn())->isNotNull();
            }
        }

        $this->select($columns);

        $connection = $this->manager->getConnection();
        $driver = $connection->getDriver();

        return $connection->query(
            $driver->select($this->queryStatement),
            $driver->getParams()
        );
    }

    /**
     * Return the relations data loaders
     * @param array<int, mixed>|false $results
     * @return array<string, \Platine\Orm\Relation\RelationLoader<TEntity>>
     */
    protected function getRelationLoaders($results): array
    {
        if (empty($this->with) || empty($results)) {
            return [];
        }

        $loaders = [];
        $attributes = $this->getWithAttributes();
        $relations = $this->mapper->getRelations();

        foreach ($attributes['with'] as $with => $callback) {
            if (!isset($relations[$with])) {
                continue;
            }

            /** @var RelationLoader<TEntity> $loader */
            $loader = $relations[$with]->getLoader($this->manager, $this->mapper, [
                'results' => $results,
                'callback' => $callback,
                'with' => isset($attributes['extra'][$with])
                            ? $attributes['extra'][$with]
                            : [],
                'immediate' => $this->immediate
            ]);

            $loaders[$with] = $loader;
        }

        return $loaders;
    }

    /**
     * Execute the aggregate
     * @return mixed
     */
    protected function executeAggregate()
    {
        $this->queryStatement->addTables([$this->mapper->getTable()]);

        if ($this->mapper->hasSoftDelete()) {
            if (!$this->withSoftDeleted) {
                $this->where($this->mapper->getSoftDeleteColumn())->isNull();
            } elseif ($this->onlySoftDeleted) {
                $this->where($this->mapper->getSoftDeleteColumn())->isNotNull();
            }
        }

        $connection = $this->manager->getConnection();
        $driver = $connection->getDriver();

        return $connection->column(
            $driver->select($this->queryStatement),
            $driver->getParams()
        );
    }

    /**
     * Check if the current entity query is read only
     * @return bool
     */
    protected function isReadOnly(): bool
    {
        return !empty($this->queryStatement->getJoins());
    }

    /**
     * Execute the transaction
     * @param Closure $callback
     *
     * @return mixed
     */
    protected function transaction(Closure $callback)
    {
        return $this->manager->getConnection()
                             ->transaction($callback);
    }
}
