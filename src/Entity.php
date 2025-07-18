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
 *  @file Entity.php
 *
 *  The Entity class
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

use JsonSerializable;
use Platine\Orm\Exception\PropertyNotFoundException;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\DataMapperInterface;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\EntityMapperInterface;
use Stringable;

/**
 * @class Entity
 * @package Platine\Orm
 * @template TEntity as Entity
 */
abstract class Entity implements JsonSerializable, Stringable
{
    /**
     * The instance of data mapper
     * @var DataMapper<TEntity>|null
     */
    private ?DataMapper $dataMapper = null;

    /**
     * The data mapper constructor arguments
     * @var array<int, mixed>
     */
    private array $dataMapperArgs = [];

    /**
     *
     * @param EntityManager<TEntity> $manager
     * @param EntityMapper<TEntity>  $mapper
     * @param array<string, mixed> $columns
     * @param array<string, \Platine\Orm\Relation\RelationLoader<TEntity>> $loaders
     * @param bool $isReadOnly
     * @param bool $isNew
     */
    final public function __construct(
        EntityManager $manager,
        EntityMapper $mapper,
        array $columns = [],
        array $loaders = [],
        bool $isReadOnly = false,
        bool $isNew = false
    ) {
        $this->dataMapperArgs = [
            $manager,
            $mapper,
            $columns,
            $loaders,
            $isReadOnly,
            $isNew
        ];
    }

    /**
     * Clone the object
     * @return void
     */
    public function __clone(): void
    {
        if ($this->dataMapper !== null) {
            $this->dataMapper = clone $this->dataMapper;
        }
    }

    /**
     * Convert entity to JSON array
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        $rawColumns = $this->mapper()->getRawColumns();
        $data = [];
        foreach ($rawColumns as $name => $value) {
            if ($this->mapper()->hasRelation($name)) {
                $relation = $this->mapper()->getRelated($name);
                if ($relation instanceof self) {
                    $data[$name] = $relation->jsonSerialize();
                } else {
                    $data[$name] = $relation;
                }
            } elseif ($this->mapper()->hasColumn($name)) {
                $data[$name] = $this->mapper()->getColumn($name);
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Shortcut to DataMapper getColumn and getRelated
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if ($this->mapper()->hasRelation($name)) {
            return $this->mapper()->getRelated($name);
        }

        if ($this->mapper()->hasColumn($name)) {
            return $this->mapper()->getColumn($name);
        }

        throw new PropertyNotFoundException(sprintf(
            'Unknown column or relation [%s]',
            $name
        ));
    }

    /**
     * Shortcut to DataMapper setColumn and setRelated
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if ($this->mapper()->hasRelation($name)) {
            if (is_array($value)) {
                foreach ($value as $entity) {
                    $this->mapper()->link($name, $entity);
                }
            } else {
                $this->mapper()->setRelated($name, $value);
            }
        } else {
            $this->mapper()->setColumn($name, $value);
        }
    }

    /**
     * Shortcut to DataMapper hasColumn and hasRelated
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->mapper()->hasRelation($name)
                || $this->mapper()->hasColumn($name);
    }

    /**
     * Shortcut to DataMapper clearColumn
     * @param string $name
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->mapper()->clearColumn($name, true);
    }

    /**
     * Return the string representation of this entity
     * @return string
     */
    public function __toString(): string
    {
        $columns = $this->mapper()->getRawColumns();
        $columnsStr = '';
        foreach ($columns as $name => $value) {
            $columnsStr .= sprintf('%s=%s, ', $name, (string) $value);
        }

        return sprintf('[%s(%s)]', __CLASS__, rtrim($columnsStr, ', '));
    }

        /**
     * Map the entity information
     * @param EntityMapperInterface<TEntity> $mapper
     */
    abstract public static function mapEntity(EntityMapperInterface $mapper): void;

    /**
     * Return the instance of data mapper
     * @return DataMapperInterface<TEntity>
     */
    final protected function mapper(): DataMapperInterface
    {
        if ($this->dataMapper === null) {
            /** @var DataMapper<TEntity> $dataMapper */
            $dataMapper = new DataMapper(...$this->dataMapperArgs);

            $this->dataMapper = $dataMapper;
        }

        return $this->dataMapper;
    }
}
