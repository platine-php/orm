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
 *  @file EntityMapper.php
 *
 *  The Entity Mapper class
 *
 *  @package    Platine\Orm\Mapper
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm\Mapper;

use Platine\Orm\Relation\ForeignKey;
use Platine\Orm\Relation\PrimaryKey;
use Platine\Orm\Relation\Relation;
use Platine\Orm\Relation\RelationFactory;

/**
 * Class EntityMapper
 * @package Platine\Orm\Mapper
 */
class EntityMapper implements EntityMapperInterface
{
    /**
     * The name of the entity
     * @var string
     */
    protected string $name = '';

    /**
     * The full entity class
     * @var string
     */
    protected string $entityClass;

    /**
     * The name of the table
     * @var string
     */
    protected string $table = '';

    /**
     * The name of the sequence
     * @var string|null
     */
    protected ?string $sequence = null;

    /**
     * The entity primary key
     * @var PrimaryKey|null
     */
    protected ?PrimaryKey $primaryKey = null;

    /**
     * The entity foreign keys
     * @var ForeignKey|null
     */
    protected ?ForeignKey $foreignKey = null;

    /**
     * The primary key generator callback
     * @var callable|null
     */
    protected $primaryKeyGenerator = null;

    /**
     * The list of columns getter
     * @var array<string, callable>
     */
    protected array $getters = [];

    /**
     * The list of columns setter
     * @var array<string, callable>
     */
    protected array $setters = [];

    /**
     * The list of columns casts
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * The list of entity relations
     * @var array<string, Relation>
     */
    protected array $relations = [];

    /**
     * The list of fillable columns
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * The list of guarded columns
     * @var array<int, string>
     */
    protected array $guarded = [];

    /**
     * The list of query filters
     * @var array<string, callable>
     */
    protected array $filters = [];

    /**
     * Whether to use soft delete
     * @var bool
     */
    protected bool $useSoftDelete = true;

    /**
     * The name of the soft delete column
     * @var string
     */
    protected string $softDeleteColumn = 'deleted_at';

    /**
     * Whether to use timestamp
     * @var bool
     */
    protected bool $useTimestamp = true;

    /**
     * The list of timestamp columns
     * @var array<int, string>
     */
    protected array $timestampColumns = ['created_at', 'updated_at'];


    /**
     * The list of events handlers
     * @var array<string, array<callable>>
     */
    protected array $eventHandlers = [];


    /**
     * Create new instance
     * @param string $entityClass
     */
    public function __construct(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritedoc}
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function primaryKey(string ...$primaryKey): self
    {
        $this->primaryKey = new PrimaryKey(...$primaryKey);

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function primaryKeyGenerator(callable $generator): self
    {
        $this->primaryKeyGenerator = $generator;

        return $this;
    }

     /**
     * {@inheritedoc}
     */
    public function sequence(string $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function casts(array $columns): self
    {
        $this->casts = $columns;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function fillable(array $columns): self
    {
        $this->fillable = $columns;

        return $this;
    }

     /**
     * {@inheritedoc}
     */
    public function guarded(array $columns): self
    {
        $this->guarded = $columns;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function filter(string $name, callable $filter): self
    {
        $this->filters[$name] = $filter;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function getter(string $column, callable $getter): self
    {
        $this->getters[$column] = $getter;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function setter(string $column, callable $setter): self
    {
        $this->setters[$column] = $setter;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function on(string $name, callable $handler): self
    {
        if (!isset($this->eventHandlers[$name])) {
            $this->eventHandlers[$name] = [];
        }

        $this->eventHandlers[$name][] = $handler;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function relation(string $name): RelationFactory
    {
        return new RelationFactory($name, function ($name, Relation $relation) {
            return $this->relations[$name] = $relation;
        });
    }

    /**
     * {@inheritedoc}
     */
    public function useSoftDelete(
        bool $value = true,
        string $column = 'deleted_at'
    ): self {
        $this->useSoftDelete = $value;
        $this->softDeleteColumn = $column;

        return $this;
    }

    /**
     * {@inheritedoc}
     */
    public function useTimestamp(
        bool $value = true,
        string $createdAt = 'created_at',
        string $updatedAt = 'updated_at'
    ): self {
        $this->useTimestamp = $value;
        $this->timestampColumns = [$createdAt, $updatedAt];

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     *
     * @return string
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            $name = $this->entityClass;

            $pos = strrpos($name, '\\');

            if ($pos !== false) {
                $name = substr($name, $pos + 1);
            }

            $name = preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $name);

            if ($name !== null) {
                $name = strtolower($name);
                $name = str_replace('-', '_', $name);
                $this->name = $name;
            }
        }

        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function getTable(): string
    {
        if (empty($this->table)) {
            $this->table = $this->getName() . 's';
        }

        return $this->table;
    }

    /**
     *
     * @return PrimaryKey
     */
    public function getPrimaryKey(): PrimaryKey
    {
        if ($this->primaryKey === null) {
            //Default Primary key if user not set it
            $this->primaryKey = new PrimaryKey('id');
        }

        return $this->primaryKey;
    }

    /**
     *
     * @return string
     */
    public function getSequence(): string
    {
        if ($this->sequence === null) {
            $primaryKey = $this->getPrimaryKey()->columns();

            $this->sequence = sprintf(
                '%s_%s_seq',
                $this->getTable(),
                $primaryKey[0]
            );
        }

        return $this->sequence;
    }

    /**
     *
     * @return ForeignKey
     */
    public function getForeignKey(): ForeignKey
    {
        if ($this->foreignKey === null) {
            $primaryKey = $this->getPrimaryKey();
            $prefix = $this->getName();

            $this->foreignKey = new class ($primaryKey, $prefix) extends ForeignKey
            {
                /**
                 *
                 * @param PrimaryKey $primaryKey
                 * @param string $prefix
                 */
                public function __construct(PrimaryKey $primaryKey, string $prefix)
                {
                    /** @var array<string, string> $columns */
                    $columns = [];

                    foreach ($primaryKey->columns() as $column) {
                        $columns[$column] = $prefix . '_' . $column;
                    }
                    parent::__construct($columns);
                }
            };
        }

        return $this->foreignKey;
    }

    /**
     *
     * @return callable|null
     */
    public function getPrimaryKeyGenerator()
    {
        return $this->primaryKeyGenerator;
    }

    /**
     *
     * @return array<string, callable>
     */
    public function getGetters(): array
    {
        return $this->getters;
    }

    /**
     *
     * @return array<string, callable>
     */
    public function getSetters(): array
    {
        return $this->setters;
    }

    /**
     *
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     *
     * @return array<string, Relation>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     *
     * @return array<int, string>
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     *
     * @return array<int, string>
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }

    /**
     *
     * @return array<string, callable>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     *
     * @return bool
     */
    public function hasSoftDelete(): bool
    {
        $column = $this->softDeleteColumn;

        return $this->useSoftDelete
               && isset($this->casts[$column])
               && $this->casts[$column] === '?date';
    }

    /**
     *
     * @return string
     */
    public function getSoftDeleteColumn(): string
    {
        return $this->softDeleteColumn;
    }

    /**
     *
     * @return bool
     */
    public function hasTimestamp(): bool
    {
        list($createdAt, $updateAt) = $this->timestampColumns;
        return $this->useTimestamp
               && isset($this->casts[$createdAt])
               && $this->casts[$createdAt] === 'date'
               && isset($this->casts[$updateAt])
               && $this->casts[$updateAt] === '?date';
    }

    /**
     *
     * @return array<int, string>
     */
    public function getTimestampColumns(): array
    {
        return $this->timestampColumns;
    }

    /**
     *
     * @return array<string, array<callable>>
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }
}
