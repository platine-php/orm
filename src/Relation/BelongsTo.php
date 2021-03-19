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
 *  @file Relation.php
 *
 *  The BelongsTo class
 *
 *  @package    Platine\Orm\Relation
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
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
 * Class BelongsTo
 * @package Platine\Orm\Relation
 */
class BelongsTo extends Relation
{

    /**
     *
     * @param DataMapper $owner
     * @param Entity|null $entity
     *
     * @return void
     */
    public function addRelatedEntity(DataMapper $owner, ?Entity $entity = null): void
    {
        if ($entity === null) {
            $columns = [];
            $mapper = $owner->getEntityManager()->getEntityMapper($this->entityClass);
        } else {
            $related = Proxy::instance()->getEntityDataMapper($entity);
            $mapper = $related->getEntityMapper();
            $columns = $related->getRawColumns();
        }

        if ($this->foreignKey === null) {
            $this->foreignKey = $mapper->getForeignKey();
        }

        foreach ($this->foreignKey->getValue($columns, true) as $fkColumn => $fkValue) {
            $owner->setColumn($fkColumn, $fkValue);
        }
    }

    /**
     * {@inheritedoc}
     * @return RelationLoader
     */
    public function getLoader(EntityManager $manager, EntityMapper $owner, $options): RelationLoader
    {
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->foreignKey === null) {
            $this->foreignKey = $related->getForeignKey();
        }

        $ids = [];
        foreach ($options['results'] as $result) {
            foreach ($this->foreignKey->getInverseValue($result, true) as $pkColumn => $pkValue) {
                $ids[$pkColumn][] = $pkValue;
            }
        }

        $queryStatement = new QueryStatement();
        $select = new EntityQuery($manager, $related, $queryStatement);

        foreach ($ids as $col => $value) {
            $value = array_unique($value);
            if (count($value) > 1) {
                $select->where($col)->in($value);
            } else {
                $select->where($col)->is(reset($value));
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
            true,
            false,
            $options['immediate']
        );
    }

    /**
     * {@inheritedoc}
     * @return Entity|null
     */
    public function getResult(DataMapper $mapper, ?callable $callback = null)
    {
        $manager = $mapper->getEntityManager();
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->foreignKey === null) {
            $this->foreignKey = $related->getForeignKey();
        }

        $queryStatement = new QueryStatement();
        $select = new EntityQuery($manager, $related, $queryStatement);

        foreach ($this->foreignKey->getInverseValue($mapper->getRawColumns(), true) as $pkColumn => $pkValue) {
            $select->where($pkColumn)->is($pkValue);
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

        return $select->get();
    }
}
