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
 *  @file RepositoryInterface.php
 *
 *  The RepositoryInterface class
 *
 *  @package    Platine\Orm
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm;

use Closure;
use Platine\Database\Query\Expression;
use Platine\Orm\Query\EntityQuery;

/**
 * @class RepositoryInterface
 * @package Platine\Orm
 * @template TEntity as Entity
 */
interface RepositoryInterface
{
    /**
     * Return the instance of EntityQuery
     * @param string|array<int, string>|array<string, Closure> $with
     * @param bool $immediate
     * @return EntityQuery<TEntity>
     */
    public function query(string|array $with = [], bool $immediate = false): EntityQuery;

    /**
     * Load with relation
     * @param string|array<int, string>|array<string, Closure> $with
     * @param bool $immediate
     * @return self<TEntity>
     */
    public function with(string|array $with, bool $immediate = false): self;

    /**
     * Set order
     * @param string|Closure|Expression|string[]|Expression[]|Closure[] $columns
     * @param string $order
     * @return self<TEntity>
     */
    public function orderBy(
        string|Closure|Expression|array $columns,
        string $order = 'ASC'
    ): self;

    /**
     * Add limit and offset
     * @param int $offset
     * @param int $limit
     * @return self<TEntity>
     */
    public function limit(int $offset, int $limit): self;

    /**
     * Apply an filters on the query
     * @param string|array<string, mixed> $filters
     * @return self<TEntity>
     */
    public function filters(string|array $filters = []): self;

    /**
     * Create the instance of Entity
     * @param array<string, mixed> $columns initial data
     * @return TEntity
     */
    public function create(array $columns = []): Entity;

    /**
     * Shortcut to "insert" and "update" the entity in data store
     * @param TEntity $entity
     * @return array<mixed>|string|int|float|bool|null
     */
    public function save(Entity $entity): array|string|int|float|bool|null;

    /**
     * Save the new entity in data store
     * @param TEntity $entity
     * @return array<mixed>|string|int|float|false|null the primary key(s) value(s)
     */
    public function insert(Entity $entity): array|string|int|float|false|null;

    /**
     * Update the existing entity in data store
     * @param TEntity $entity
     * @return bool
     */
    public function update(Entity $entity): bool;

    /**
     * Delete the entity
     * @param TEntity $entity
     * @param bool $force
     * @return bool
     */
    public function delete(Entity $entity, bool $force = false): bool;

    /**
     *
     * @param array<int, string> $columns
     * @return TEntity[]
     */
    public function all(array $columns = []): array;

    /**
     * Find one entity instance
     * @param array<mixed>|string|int|float $id
     *
     * @return TEntity|null
     */
    public function find(array|string|int|float $id): ?Entity;

    /**
     * Find one entity instance using some conditions
     * @param array<string, mixed> $conditions
     *
     * @return TEntity|null
     */
    public function findBy(array $conditions): ?Entity;

    /**
     * Find the list of record using many primary key
     * @param mixed ...$ids
     * @return TEntity[]
     */
    public function findAll(mixed ...$ids): array;

    /**
     * Find the list of record using some conditions
     * @param array<string, mixed> $conditions
     * @return TEntity[]
     */
    public function findAllBy(array $conditions): array;
}
