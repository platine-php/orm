<?php

/**
 * Platine Database
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
 *  @file DataMapper.php
 *
 *  The Data Mapper class
 *
 *  @package    Platine\Orm\Mapper
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm\Mapper;

use DateTime;
use Platine\Database\Query\Select;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Exception\EntityStateException;
use Platine\Orm\Exception\PropertyNotFoundException;
use Platine\Orm\Exception\RelationNotFoundException;
use Platine\Orm\Relation\BelongsTo;
use Platine\Orm\Relation\HasMany;
use Platine\Orm\Relation\HasOne;
use Platine\Orm\Relation\HasRelation;
use Platine\Orm\Relation\ShareMany;
use Platine\Orm\Relation\ShareOne;
use Platine\Orm\Relation\ShareRelation;
use RuntimeException;

/**
 * Class DataMapper
 * @package Platine\Orm\Mapper
 */
class DataMapper implements DataMapperInterface
{
    /**
     * The raw columns data
     * @var array<string, mixed>
     */
    protected array $rawColumns = [];

    /**
     * The current columns data. After refresh, etc.
     * @var array<string, mixed>
     */
    protected array $columns = [];

    /**
     * The list of relation loaders
     * @var array<string, \Platine\Orm\Relation\RelationLoader>
     */
    protected array $loaders = [];

    /**
     * The Entity manager instance
     * @var EntityManager
     */
    protected EntityManager $manager;

    /**
     *
     * @var EntityMapper
     */
    protected EntityMapper $mapper;

    /**
     * Whether the data is read only
     * @var bool
     */
    protected bool $isReadOnly = false;

    /**
     * Whether the data is new
     * @var bool
     */
    protected bool $isNew = false;

    /**
     * Whether the data is deleted
     * @var bool
     */
    protected bool $deleted = false;

    /**
     * Whether the data need to refreshed from data store
     * @var bool
     */
    protected bool $refresh = false;

    /**
     * The name of the sequence to use
     * @var string|null
     */
    protected ?string $sequence = null;

    /**
     * The list of modified data
     * @var array<string, mixed>
     */
    protected array $modified = [];

    /**
     * The list of relations loaded data (cached)
     * @var array<string, mixed>
     */
    protected array $relations = [];

    /**
     * The list of pending links
     * @var array<int, array<string, mixed>>
     */
    protected array $pendingLinks = [];

    /**
     * Create new instance
     * @param EntityManager $manager
     * @param EntityMapper $mapper
     * @param array<string, mixed> $columns
     * @param array<string, mixed> $loaders
     * @param bool $isReadOnly
     * @param bool $isNew
     */
    public function __construct(
        EntityManager $manager,
        EntityMapper $mapper,
        array $columns,
        array $loaders,
        bool $isReadOnly,
        bool $isNew
    ) {
        $this->manager = $manager;
        $this->mapper = $mapper;
        $this->loaders = $loaders;
        $this->isReadOnly = $isReadOnly;
        $this->isNew = $isNew;
        $this->rawColumns = $columns;

        if ($isNew && !empty($columns)) {
            $this->rawColumns = [];
            $this->fill($columns);
        }
    }

    /**
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->manager;
    }

    /**
     *
     * @return EntityMapper
     */
    public function getEntityMapper(): EntityMapper
    {
        return $this->mapper;
    }

    /**
     * {@inheritedoc}
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * {@inheritedoc}
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * {@inheritedoc}
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * {@inheritedoc}
     */
    public function wasModified(): bool
    {
        return !empty($this->modified) || !empty($this->pendingLinks);
    }

    /**
     * {@inheritedoc}
     */
    public function getColumn(string $name)
    {
        if ($this->refresh) {
            $this->hydrate();
        }

        if ($this->deleted) {
            throw new EntityStateException('The record was deleted');
        }

        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }

        if (!array_key_exists($name, $this->rawColumns)) {
            throw new PropertyNotFoundException(sprintf(
                'Unknown column [%s]',
                $name
            ));
        }

        $value = $this->rawColumns[$name];
        $casts = $this->mapper->getCasts();

        if (isset($casts[$name])) {
            $value = $this->castGet($value, $casts[$name]);
        }

        $primaryKey = $this->mapper->getPrimaryKey();
        if ($name === (string)$primaryKey) {
            return $this->columns[$name] = $value;
        }

        $getters = $this->mapper->getGetters();
        if (isset($getters[$name])) {
            $callback = $getters[$name];
            $value = ($callback)($value, $this);
        }

        return $this->columns[$name] = $value;
    }

    /**
     * {@inheritedoc}
     */
    public function setColumn(string $name, $value): void
    {
        if ($this->isReadOnly) {
            throw new EntityStateException('The record is readonly');
        }

        if ($this->deleted) {
            throw new EntityStateException('The record was deleted');
        }

        if ($this->refresh) {
            $this->hydrate();
        }

        $casts = $this->mapper->getCasts();
        $setters = $this->mapper->getSetters();

        if (isset($setters[$name])) {
            $callback = $setters[$name];
            $value = ($callback)($value, $this);
        }

        if (isset($casts[$name])) {
            $value = $this->castSet($value, $casts[$name]);
        }

        $this->modified[$name] = true;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

    /**
     * {@inheritedoc}
     */
    public function clearColumn(string $name): void
    {
        unset($this->columns[$name]);
    }

     /**
     * {@inheritedoc}
     */
    public function hasColumn(string $column): bool
    {
        return array_key_exists($column, $this->columns)
                || array_key_exists($column, $this->rawColumns);
    }

    /**
     * {@inheritedoc}
     */
    public function getModifiedColumns(): array
    {
        return array_keys($this->modified);
    }

    /**
     * {@inheritedoc}
     */
    public function getRawColumns(): array
    {
        return $this->rawColumns;
    }

    /**
     * {@inheritedoc}
     */
    public function setRawColumn(string $name, $value): void
    {
        $this->modified[$name] = true;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

     /**
     * {@inheritedoc}
     */
    public function getRelated(string $name)
    {
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        $relations = $this->mapper->getRelations();

        $cacheKey = $name;

        $index = strpos($name, ':');
        if ($index !== false) {
            $name = substr($name, $index + 1);
        }

        if (!isset($relations[$name])) {
            throw new RelationNotFoundException(sprintf(
                'Unknown relation [%s]',
                $name
            ));
        }

        $this->hydrate();

        if (isset($this->relations[$cacheKey])) {
            return $this->relations[$cacheKey];
        }

        if (isset($this->loaders[$cacheKey])) {
            return $this->relations[$cacheKey] = $this->loaders[$name]->getResult($this);
        }

        return $this->relations[$cacheKey] = $relations[$name]->getResult($this);
    }

    /**
     * {@inheritedoc}
     */
    public function setRelated(string $name, ?Entity $entity = null): void
    {
        $relations = $this->mapper->getRelations();

        if (!isset($relations[$name])) {
            throw new RelationNotFoundException(sprintf(
                'Unknown relation [%s]',
                $name
            ));
        }

        /** @var BelongsTo|HasOne|HasMany $rel */
        $rel = $relations[$name];

        if (!($rel instanceof BelongsTo) && !($rel instanceof HasRelation)) {
            throw new RuntimeException('Unsupported relation type');
        }

        if ($entity === null && !($rel instanceof BelongsTo)) {
            throw new RuntimeException('Unsupported relation type');
        }

        $rel->addRelatedEntity($this, $entity);
    }

    /**
     * {@inheritedoc}
     */
    public function clearRelated(string $name, bool $loaders = false): void
    {
        $cacheKey = $name;

        $index = strpos($name, ':');
        if ($index !== false) {
            $name = substr($name, $index + 1);
        }

        unset($this->relations[$cacheKey]);

        if ($loaders) {
            unset($this->loaders[$name]);
        }
    }

    /**
     * {@inheritedoc}
     */
    public function hasRelation(string $relation): bool
    {
        $relations = $this->mapper->getRelations();

        return isset($relations[$relation]);
    }

    /**
     * {@inheritedoc}
     */
    public function link(string $relation, Entity $entity): void
    {
        $this->setLink($relation, $entity, true);
    }

    /**
     * {@inheritedoc}
     */
    public function unlink(string $relation, Entity $entity): void
    {
        $this->setLink($relation, $entity, false);
    }

    /**
     * {@inheritedoc}
     */
    public function refresh(): void
    {
        $this->refresh = true;
    }

    /**
     * {@inheritedoc}
     */
    public function fill(array $columns): void
    {
        $fillable = $this->mapper->getFillable();
        $guarded = $this->mapper->getGuarded();

        if (!empty($fillable)) {
            $columns = array_intersect_key($columns, array_flip($fillable));
        } elseif (!empty($guarded)) {
            $columns = array_diff_key($columns, array_flip($guarded));
        }

        foreach ($columns as $name => $value) {
            $this->setColumn($name, $value);
        }
    }

    /**
     * Mark the entity as saved
     * @param mixed $id
     * @return bool
     */
    public function markAsSaved($id): bool
    {
        $primaryKey = $this->mapper->getPrimaryKey();

        if (!$primaryKey->isComposite()) {
            $columns = $primaryKey->columns();
            $this->rawColumns[$columns[0]] = $id;
        } else {
            foreach ($primaryKey->columns() as $pkColumn) {
                $this->rawColumns[$pkColumn] = $id[$pkColumn];
            }
        }

        $this->refresh = true;
        $this->isNew = false;
        $this->modified = [];

        if (!empty($this->pendingLinks)) {
            $this->executePendingLinkage();
        }

        return true;
    }

    /**
     * Mark the entity as updated
     * @param string|null $updatedAt
     * @return bool
     */
    public function markAsUpdated(?string $updatedAt = null): bool
    {
        if ($updatedAt !== null) {
            list(, $column) = $this->mapper->getTimestampColumns();
            unset($this->columns[$column]);
            $this->rawColumns[$column] = $updatedAt;
        }

        $this->modified = [];

        if (!empty($this->pendingLinks)) {
            $this->executePendingLinkage();
        }

        return true;
    }

    /**
     * Mark the entity as deleted
     * @return bool
     */
    public function markAsDeleted(): bool
    {
        return $this->deleted = true;
    }

    /**
     * Execute the pending links
     * @return void
     */
    public function executePendingLinkage(): void
    {
        foreach ($this->pendingLinks as $item) {
            /** @var ShareOne|ShareMany $rel */
            $rel = $item['relation'];

            if (isset($item['link'])) {
                $rel->link($this, $item['entity']);
            } else {
                $rel->unlink($this, $item['entity']);
            }
        }

        $this->pendingLinks = [];
    }

    /**
     * Get fresh data from data store
     * @return void
     */
    protected function hydrate(): void
    {
        if (!$this->refresh) {
            return;
        }

        $select = new Select($this->manager->getConnection(), $this->mapper->getTable());

        foreach ($this->mapper->getPrimaryKey()->getValue($this->rawColumns, true) as $pkColumn => $pkValue) {
            $select->where($pkColumn)->is($pkValue);
        }

        $columns = $select->select()->fetchAssoc()->get();

        if ($columns === false) {
            $this->deleted = true;
            return;
        }

        $this->rawColumns = $columns;
        $this->columns = [];
        $this->relations = [];
        $this->loaders = [];
        $this->refresh = false;
    }

    /**
     * Cast the value to the given type for get
     * @param mixed $value
     * @param string $type
     *
     * @return mixed
     */
    protected function castGet($value, string $type)
    {
        $original = $type;

        if ($type[0] === '?') {
            if ($value === null) {
                return null;
            }
            $type = substr($type, 1);
        }

        switch ($type) {
            case 'int':
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
            case 'double':
                $value = (float) $value;
                break;
            case 'bool':
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'string':
                $value = (string) $value;
                break;
            case 'date':
                $value = /** @var DateTime $value */ $value->format($this->manager->getDateFormat());
                break;
            case 'json':
            case 'json-assoc':
                $value = json_encode($value);
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Invalid cast type [%s]',
                    $original
                ));
        }

        return $value;
    }

    /**
     * Cast the value to the given type for set
     * @param mixed $value
     * @param string $type
     *
     * @return mixed
     */
    protected function castSet($value, string $type)
    {
        $original = $type;

        if ($type[0] === '?') {
            if ($value === null) {
                return null;
            }
            $type = substr($type, 1);
        }

        switch ($type) {
            case 'int':
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
            case 'double':
                $value = (float) $value;
                break;
            case 'bool':
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'string':
                $value = (string) $value;
                break;
            case 'date':
                $value = DateTime::createFromFormat($this->manager->getDateFormat(), $value);
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            case 'json-assoc':
                $value = json_decode($value, true);
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Invalid cast type [%s]',
                    $original
                ));
        }

        return $value;
    }

    /**
     * Set
     * @param string $relation
     * @param Entity $entity
     * @param bool $link
     * @return void
     */
    private function setLink(string $relation, Entity $entity, bool $link): void
    {
        $relations = $this->mapper->getRelations();

        if (!isset($relations[$relation])) {
            throw new RelationNotFoundException(sprintf(
                'Unknown relation [%s]',
                $relation
            ));
        }

        /** @var ShareOne|ShareMany $rel  */
        $rel = $relations[$relation];
        if (!($rel instanceof ShareRelation)) {
            throw new RuntimeException('Unsupported relation type');
        }

        $this->pendingLinks[] = [
            'relation' => $rel,
            'entity' => $entity,
            'link' => $link
        ];
    }
}
