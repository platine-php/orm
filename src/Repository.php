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
 *  @file Repository.php
 *
 *  The Repository class
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
use Platine\Database\Connection;
use Platine\Database\Query\Expression;
use Platine\Database\Query\Insert;
use Platine\Database\Query\Update;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Exception\EntityStateException;
use Platine\Orm\Mapper\Proxy;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\RepositoryInterface;


/**
 * @class Repository
 * @package Platine\Orm
 * @template TEntity as Entity
 * @implements RepositoryInterface<TEntity>
 */
class Repository implements RepositoryInterface
{
    /**
     * The list of relation to load with the query
     * @var array<int, string>|array<string, Closure>
     */
    protected array $with = [];

    /**
     * Whether need load relation data immediately
     * @var bool
     */
    protected bool $immediate = false;

    /**
     * The order by column(s)
     * @var string|Closure|Expression|string[]|Expression[]|Closure[]
     */
    protected string|Closure|Expression|array $orderColumns = '';

    /**
     * The order direction
     * @var string
     */
    protected string $orderDir = 'ASC';

    /**
     * The offset to use
     * @var int
     */
    protected int $offset = -1;

    /**
     * The limit to use
     * @var int
     */
    protected int $limit = 0;

    /**
     * The filters list
     * @var array<string, mixed>
     */
    protected array $filters = [];

    /**
     * Create new instance
     * @param EntityManager<TEntity> $manager
     * @param class-string<TEntity> $entityClass
     */
    public function __construct(
        protected EntityManager $manager,
        protected string $entityClass
    ) {
    }

    /**
     * {@inheritedoc}
     */
    public function query(string|array $with = [], bool $immediate = false): EntityQuery
    {
        if (is_string($with)) {
            $with = [$with];
        }

        if (empty($with) && count($this->with) > 0) {
            $with = $this->with;

            $this->with = [];
        }
        $this->immediate = false;

        $query = $this->manager->query($this->entityClass);
        $query->with($with, $immediate);

        if (!empty($this->orderColumns)) {
            $query->orderBy($this->orderColumns, $this->orderDir);

            $this->orderColumns = '';
            $this->orderDir = 'ASC';
        }

        if ($this->offset >= 0 && $this->limit >= 0) {
            $query->offset($this->offset)
                  ->limit($this->limit);

            $this->offset = -1;
            $this->limit = 0;
        }

        $this->setFilters($query);

        return $query;
    }


    /**
     * {@inheritedoc}
     */
    public function with(string|array $with, bool $immediate = false): self
    {
        if (!is_array($with)) {
            $with = [$with];
        }
        $this->with = $with;
        $this->immediate = $immediate;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function orderBy(
        string|Closure|Expression|array $columns,
        string $order = 'ASC'
    ): self {
        $this->orderColumns = $columns;
        $this->orderDir = $order;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function limit(int $offset, int $limit): self
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function filters(string|array $filters = []): self
    {
        if (is_string($filters)) {
            $filters = [$filters => true];
        }

        $this->filters = $filters;

        return $this;
    }

    /**
     * {@inheritedoc}
     * @return TEntity[]
     */
    public function all(array $columns = []): array
    {
        return $this->query()->all($columns);
    }

    /**
     * {@inheritedoc}
     */
    public function create(array $columns = []): Entity
    {
        $mapper = $this->manager->getEntityMapper($this->entityClass);

        return new $this->entityClass(
            $this->manager,
            $mapper,
            $columns,
            [],
            false,
            true
        );
    }

    /**
     * {@inheritedoc}
     * @return TEntity|null
     */
    public function find(array|string|int|float $id): ?Entity
    {
        return $this->query()->find($id);
    }

    /**
     * {@inheritedoc}
     */
    public function findBy(array $conditions): ?Entity
    {
        $query = $this->query();
        foreach ($conditions as $name => $value) {
            $query->where($name)->is($value);
        }
        return $query->get();
    }

    /**
     * {@inheritedoc}
     */
    public function findAll(mixed ...$ids): array
    {
        return $this->query()->findAll(...$ids);
    }

    /**
     * {@inheritedoc}
     */
    public function findAllBy(array $conditions): array
    {
        $query = $this->query();
        foreach ($conditions as $name => $value) {
            $query->where($name)->is($value);
        }
        return $query->all();
    }

    /**
     * {@inheritedoc}
     */
    public function save(Entity $entity): array|string|int|float|bool|null
    {
        $data = Proxy::instance()->getEntityDataMapper($entity);

        if ($data->isNew()) {
            return (bool) $this->insert($entity);
        }

        return $this->update($entity);
    }

    /**
     * {@inheritedoc}
     */
    public function insert(Entity $entity): array|string|int|float|false|null
    {
        $data = Proxy::instance()->getEntityDataMapper($entity);
        $mapper = $data->getEntityMapper();
        $eventsHandlers = $mapper->getEventHandlers();

        if ($data->isDeleted()) {
            throw new EntityStateException('The record was deleted');
        }

        if (!$data->isNew()) {
            throw new EntityStateException('The record was already saved');
        }

        $connection = $this->manager->getConnection();
        $id = $connection->transaction(function (Connection $connection) use ($data, $mapper) {
            $columns = $data->getRawColumns();
            $pkGenerator = $mapper->getPrimaryKeyGenerator();
            if ($pkGenerator !== null) {
                $pkData = $pkGenerator($data);

                if (is_array($pkData)) {
                    foreach ($pkData as $pkColumn => $pkValue) {
                        $columns[$pkColumn] = $pkValue;
                    }
                } else {
                    $pkColumns = $mapper->getPrimaryKey()->columns();
                    $columns[$pkColumns[0]] = $pkData;
                }
            }

            if ($mapper->hasTimestamp()) {
                list($createdAtCol, $updatedAtCol) = $mapper->getTimestampColumns();
                $columns[$createdAtCol] = date($this->manager->getDateFormat());
                $columns[$updatedAtCol] = null;
            }

            (new Insert($connection))->insert($columns)->into($mapper->getTable());

            if ($pkGenerator !== null) {
                return isset($pkData) ? $pkData : false;
            }

            return $connection->getPDO()->lastInsertId($mapper->getSequence());
        });

        if ($id === false) {
            return false;
        }

        $data->markAsSaved($id);

        if (isset($eventsHandlers['save'])) {
            foreach ($eventsHandlers['save'] as $callback) {
                $callback($entity, $data);
            }
        }

        return $id;
    }

    /**
     * {@inheritedoc}
     */
    public function update(Entity $entity): bool
    {
        $data = Proxy::instance()->getEntityDataMapper($entity);
        $mapper = $data->getEntityMapper();
        $eventsHandlers = $mapper->getEventHandlers();

        if ($data->isDeleted()) {
            throw new EntityStateException('The record was deleted');
        }

        if ($data->isNew()) {
            throw new EntityStateException('Can\'t update an unsaved entity');
        }

        if (!$data->wasModified()) {
            return true;
        }

        $modified = $data->getModifiedColumns();
        if (!empty($modified)) {
            $connection = $this->manager->getConnection();
            $result = $connection->transaction(function (Connection $connection) use ($data, $mapper, $modified) {
                $columns = array_intersect_key($data->getRawColumns(), array_flip($modified));

                $updatedAt = null;

                if ($mapper->hasTimestamp()) {
                    list(, $updatedAtCol) = $mapper->getTimestampColumns();
                    $columns[$updatedAtCol] = $updatedAt = date($this->manager->getDateFormat());
                }
                $data->markAsUpdated($updatedAt);

                $update = new Update($connection, $mapper->getTable());

                $primaryKeys = $mapper->getPrimaryKey()->getValue($data->getRawColumns(), true);
                if (is_array($primaryKeys)) {
                    foreach ($primaryKeys as $pkColumn => $pkValue) {
                        $update->where($pkColumn)->is($pkValue);
                    }
                }

                return $update->set($columns) >= 0;
            });

            if ($result === false) {
                return false;
            }

            if (isset($eventsHandlers['update'])) {
                foreach ($eventsHandlers['update'] as $callback) {
                    $callback($entity, $data);
                }
            }

            return true;
        }

        $connection = $this->manager->getConnection();
        return $connection->transaction(function (Connection $connection) use ($data) {
            $data->executePendingLinkage();

            return true;
        });
    }

    /**
     * {@inheritedoc}
     */
    public function delete(Entity $entity, bool $force = false): bool
    {
        $data = Proxy::instance()->getEntityDataMapper($entity);
        $mapper = $data->getEntityMapper();
        $eventsHandlers = $mapper->getEventHandlers();
        $connection = $this->manager->getConnection();

        $result = $connection->transaction(function () use ($data, $mapper, $force) {
            if ($data->isDeleted()) {
                throw new EntityStateException('The record was deleted');
            }

            if ($data->isNew()) {
                throw new EntityStateException('Can\'t delete an unsaved entity');
            }

            $delete = new EntityQuery($this->manager, $mapper);

            foreach ($mapper->getPrimaryKey()->getValue($data->getRawColumns(), true) as $pkColumn => $pkValue) {
                $delete->where($pkColumn)->is($pkValue);
            }

            return (bool) $delete->delete($force);
        });

        if ($result === false) {
            return false;
        }

        if (isset($eventsHandlers['delete'])) {
            foreach ($eventsHandlers['delete'] as $callback) {
                $callback($entity, $data);
            }
        }

        //Note this need call after events handlers
        //because some handlers can't access
        //entity attributes after mark as delete
        $data->markAsDeleted();

        return true;
    }

    /**
     * {@inheritedoc}
     */
    public function exists(array $conditions): bool
    {
        return $this->findBy($conditions) !== null;
    }

    /**
     * {@inheritedoc}
     */
    public function existIgnore(array $conditions, mixed $value, string $field = 'id'): bool
    {
        $o = $this->findBy($conditions);

        return $o !== null && $o->{$field} != $value; // don't use ===
    }

    /**
     * Set the filters
     * @param EntityQuery<TEntity> $query
     * @return $this
     */
    protected function setFilters(EntityQuery $query): self
    {
        if (!empty($this->filters)) {
            $query->filter($this->filters);

            $this->filters = [];
        }

        return $this;
    }
}
