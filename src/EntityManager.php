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
 *  @file EntityManager.php
 *
 *  The EntityManager class
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

use Platine\Database\Connection;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Query\EntityQuery;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * @class EntityManager
 * @package Platine\Orm
 * @template TEntity as Entity
 */
class EntityManager
{
    /**
     * The connection
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The date format
     * @var string
     */
    protected string $dateFormat;

    /**
     * The cache of already resolved entities mappers
     * @var array<string, EntityMapper<TEntity>>
     */
    protected array $entityMappers = [];

    /**
     * Create new instance
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->dateFormat = $connection->getDriver()->getDateFormat();
    }

    /**
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Get an instance of EntityQuery
     * @param class-string<TEntity> $entityClass
     * @return EntityQuery<TEntity>
     */
    public function query(string $entityClass): EntityQuery
    {
        return new EntityQuery($this, $this->getEntityMapper($entityClass));
    }

    /**
     * Resolve the entity mapper for the given entity class
     * @param class-string<TEntity> $entityClass
     * @return EntityMapper<TEntity>
     */
    public function getEntityMapper(string $entityClass): EntityMapper
    {
        if (isset($this->entityMappers[$entityClass])) {
            return $this->entityMappers[$entityClass];
        }

        try {
            $reflection = new ReflectionClass($entityClass);
        } catch (ReflectionException $ex) {
            throw new RuntimeException(
                sprintf(
                    'Error when build the mapper for entity [%s]',
                    $entityClass
                ),
                0,
                $ex
            );
        }

        if (!$reflection->isSubclassOf(Entity::class)) {
            throw new RuntimeException(sprintf(
                '[%s] must extend [%s]',
                $entityClass,
                Entity::class
            ));
        }

        $mapper = new EntityMapper($entityClass);

        $callback = $entityClass . '::mapEntity';
        if (is_callable($callback)) {
            $callback($mapper);
        }

        return $this->entityMappers[$entityClass] = $mapper;
    }
}
