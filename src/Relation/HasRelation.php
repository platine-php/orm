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
 *  @file HasRelation.php
 *
 *  The HasRelation class
 *
 *  @package    Platine\Orm\Relation
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */

declare(strict_types=1);

namespace Platine\Orm\Relation;

use Platine\Database\Query\QueryStatement;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\Proxy;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\Query\Query;

/**
 * @class HasRelation
 * @package Platine\Orm\Relation
 * @template TEntity as Entity
 * @extends Relation<TEntity>
 */
abstract class HasRelation extends Relation
{
    /**
     * Whether is many or not
     * @var bool
     */
    protected bool $hasMany = false;

    /**
     * Create new instance
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     */
    public function __construct(
        string $entityClass,
        ?ForeignKey $foreignKey = null
    ) {
        parent::__construct($entityClass, $foreignKey);
    }

    /**
     *
     * @param DataMapper<TEntity> $owner
     * @param TEntity $entity
     *
     * @return void
     */
    public function addRelatedEntity(DataMapper $owner, Entity $entity): void
    {
        $mapper = $owner->getEntityMapper();
        if ($this->foreignKey === null) {
            $this->foreignKey = $mapper->getForeignKey();
        }
        $related = Proxy::instance()->getEntityDataMapper($entity);

        $foreignKeys = $this->foreignKey->getValue($owner->getRawColumns(), true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $related->setColumn($fkColumn, $fkValue);
            }
        }
    }

    /**
     * {@inheritedoc}
     * @param EntityManager<TEntity> $manager
     * @param EntityMapper<TEntity> $owner
     * @param array<string, mixed> $options
     *
     * @return RelationLoader<TEntity>
     */
    public function getLoader(
        EntityManager $manager,
        EntityMapper $owner,
        array $options
    ): RelationLoader {
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        /** @var array<string, array<mixed>> $ids */
        $ids = [];

        $primaryKey = $owner->getPrimaryKey();
        foreach ($options['results'] as $result) {
            $primaryKeys = $primaryKey->getValue($result, true);
            if (is_array($primaryKeys)) {
                foreach ($primaryKeys as $pkColumn => $pkValue) {
                    $ids[$pkColumn][] = $pkValue;
                }
            }
        }

        $queryStatement = new QueryStatement();
        $select = new EntityQuery($manager, $related, $queryStatement);

        $foreignKeys = $this->foreignKey->getValue($ids, true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $select->where($fkColumn)->in([$fkValue]);
            }
        }

        if ($options['callback'] !== null) {
            $callback = $options['callback'];
            $callback(new Query($queryStatement));
        }

        $select->with($options['with'], $options['immediate']);

        return new RelationLoader(
            $select,
            $this->foreignKey,
            false,
            $this->hasMany,
            $options['immediate']
        );
    }

    /**
     * {@inheritedoc}
     * @param DataMapper<TEntity> $mapper
     *
     * @return TEntity|array<TEntity>|null
     */
    public function getResult(DataMapper $mapper, ?callable $callback = null): Entity|array|null
    {
        $manager = $mapper->getEntityManager();
        $owner = $mapper->getEntityMapper();
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        $queryStatement = new QueryStatement();
        $select = new EntityQuery($manager, $related, $queryStatement);

        $foreignKeys = $this->foreignKey->getValue($mapper->getRawColumns(), true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $select->where($fkColumn)->is($fkValue);
            }
        }

        if ($this->queryCallback !== null || $callback !== null) {
            $query = $select;

            if ($this->queryCallback !== null) {
                ($this->queryCallback)($query);
            }

            if ($callback !== null) {
                $callback($query);
            }
        }

        return $this->hasMany
                ? $select->all()
                : $select->get();
    }
}
