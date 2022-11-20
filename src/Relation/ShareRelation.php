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
 *  @file ShareRelation.php
 *
 *  The ShareRelation class
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

use Platine\Database\Query\Delete;
use Platine\Database\Query\Insert;
use Platine\Database\Query\Join;
use Platine\Database\Query\QueryStatement;
use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;
use Platine\Orm\Mapper\Proxy;
use Platine\Orm\Query\EntityQuery;
use Platine\Orm\Query\Query;
use Platine\Orm\Relation\ForeignKey;
use Platine\Orm\Relation\Junction;
use Platine\Orm\Relation\Relation;
use Platine\Orm\Relation\RelationLoader;

/**
 * Class ShareRelation
 * @package Platine\Orm\Relation
 */
abstract class ShareRelation extends Relation
{
    /**
     * Whether is many or not
     * @var bool
     */
    protected bool $hasMany = false;

    /**
     * The junction instance
     * @var Junction|null
     */
    protected ?Junction $junction = null;

    /**
     * Create new instance
     * @param class-string $entityClass
     * @param ForeignKey|null $foreignKey
     * @param Junction|null $junction
     */
    public function __construct(
        string $entityClass,
        ?ForeignKey $foreignKey = null,
        ?Junction $junction = null
    ) {
        parent::__construct($entityClass, $foreignKey);
        $this->junction = $junction;
    }

        /**
     *
     * @param DataMapper $mapper
     * @param Entity $entity
     *
     * @return bool
     */
    public function link(DataMapper $mapper, Entity $entity): bool
    {
        $manager = $mapper->getEntityManager();
        $owner = $mapper->getEntityMapper();
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        /** @var array<string, mixed> $values */
        $values = [];

        $foreignKeys = $this->foreignKey->getValue($mapper->getRawColumns(), true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $values[$fkColumn] = $fkValue;
            }
        }

        $columns = Proxy::instance()->getEntityDataMapper($entity)->getRawColumns();
        foreach ($this->junction->columns() as $pkColumn => $fkColumn) {
            $values[$fkColumn] = $columns[$pkColumn];
        }

        $cmd = new Insert($manager->getConnection());
        $cmd->insert($values);

        return (bool) $cmd->into($this->junction->table());
    }

    /**
     *
     * @param DataMapper $mapper
     * @param Entity $entity
     *
     * @return bool
     */
    public function unlink(DataMapper $mapper, Entity $entity): bool
    {
        $manager = $mapper->getEntityManager();
        $owner = $mapper->getEntityMapper();
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        /** @var array<string, mixed> $values */
        $values = [];

        $foreignKeys = $this->foreignKey->getValue($mapper->getRawColumns(), true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $values[$fkColumn] = $fkValue;
            }
        }

        $columns = Proxy::instance()->getEntityDataMapper($entity)->getRawColumns();
        foreach ($this->junction->columns() as $pkColumn => $fkColumn) {
            $values[$fkColumn] = $columns[$pkColumn];
        }

        $cmd = new Delete($manager->getConnection(), $this->junction->table());

        foreach ($values as $column => $value) {
            $cmd->where($column)->is($value);
        }

        return (bool) $cmd->delete();
    }

    /**
     * {@inheritedoc}
     * @return RelationLoader
     */
    public function getLoader(EntityManager $manager, EntityMapper $owner, array $options): RelationLoader
    {
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        $junctionTable = $this->junction->table();
        $joinTable = $related->getTable();

        /** @var array<string, array<mixed>> $ids */
        $ids = [];
        foreach ($options['results'] as $result) {
            $primaryKeys = $owner->getPrimaryKey()->getValue($result, true);
            if (is_array($primaryKeys)) {
                foreach ($primaryKeys as $pkColumn => $pkValue) {
                    $ids[$pkColumn][] = $pkValue;
                }
            }
        }

        $queryStatement = new QueryStatement();
        $select = new class ($manager, $related, $queryStatement, $junctionTable) extends EntityQuery
        {
            /**
             *
             * @var string
             */
            protected string $junctionTable;

            /**
             *
             * @param EntityManager $manager
             * @param EntityMapper $mapper
             * @param QueryStatement $queryStatement
             * @param string $table
             */
            public function __construct(
                EntityManager $manager,
                EntityMapper $mapper,
                QueryStatement $queryStatement,
                string $table
            ) {
                parent::__construct($manager, $mapper, $queryStatement);
                $this->junctionTable = $table;
            }

            /**
             *
             * @return EntityQuery
             */
            protected function buildQuery(): EntityQuery
            {
                $this->locked = true;
                $this->queryStatement->addTables([$this->junctionTable]);

                return $this;
            }

            /**
             *
             * @return bool
             */
            protected function isReadOnly(): bool
            {
                return count($this->queryStatement->getJoins()) > 1;
            }
        };

        $linkKey = new ForeignKey(array_map(function ($value) use ($junctionTable) {
            return 'hidden_' . $junctionTable . $value;
        }, $this->foreignKey->columns()));

        $select->join($joinTable, function (Join $join) use ($junctionTable, $joinTable) {
            foreach ($this->junction->columns() as $pkColumn => $fkColumn) {
                $join->on($junctionTable . '.' . $fkColumn, $joinTable . '.' . $pkColumn);
            }
        });

        $foreignKeys = $this->foreignKey->getValue($ids, true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $fkValue) {
                $select->where($junctionTable . '.' . $fkColumn)->in($fkValue);
            }
        }

        $queryStatement->addColumn($joinTable . '.*');

        $linkKeyCols = $linkKey->columns();
        foreach ($this->foreignKey->columns() as $pkColumn => $fkColumn) {
            $queryStatement->addColumn($junctionTable . '.' . $fkColumn, $linkKeyCols[$pkColumn]);
        }

        if ($options['callback'] !== null) {
            $callback = $options['callback'];
            $callback(new Query($queryStatement));
        }

        $select->with($options['with'], $options['immediate']);

        return new RelationLoader(
            $select,
            $linkKey,
            false,
            $this->hasMany,
            $options['immediate']
        );
    }

    /**
     * {@inheritedoc}
     */
    public function getResult(DataMapper $mapper, ?callable $callback = null)
    {
        $manager = $mapper->getEntityManager();
        $owner = $mapper->getEntityMapper();
        $related = $manager->getEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        $junctionTable = $this->junction->table();
        $joinTable = $related->getTable();

        $queryStatement = new QueryStatement();
        $select = new class ($manager, $related, $queryStatement, $junctionTable) extends EntityQuery
        {
            /**
             *
             * @var string
             */
            protected string $junctionTable;

            /**
             *
             * @param EntityManager $manager
             * @param EntityMapper $mapper
             * @param QueryStatement $queryStatement
             * @param string $table
             */
            public function __construct(
                EntityManager $manager,
                EntityMapper $mapper,
                QueryStatement $queryStatement,
                string $table
            ) {
                parent::__construct($manager, $mapper, $queryStatement);
                $this->junctionTable = $table;
            }

            /**
             *
             * @return EntityQuery
             */
            protected function buildQuery(): EntityQuery
            {
                $this->locked = true;
                $this->queryStatement->addTables([$this->junctionTable]);

                return $this;
            }

            /**
             *
             * @return bool
             */
            protected function isReadOnly(): bool
            {
                return count($this->queryStatement->getJoins()) > 1;
            }
        };

        $select->join($joinTable, function (Join $join) use ($junctionTable, $joinTable) {
            foreach ($this->junction->columns() as $pkColumn => $fkColumn) {
                $join->on($junctionTable . '.' . $fkColumn, $joinTable . '.' . $pkColumn);
            }
        });

         $foreignKeys = $this->foreignKey->getValue($mapper->getRawColumns(), true);
        if (is_array($foreignKeys)) {
            foreach ($foreignKeys as $fkColumn => $value) {
                $select->where($junctionTable . '.' . $fkColumn)->is($value);
            }
        }

        $queryStatement->addColumn($joinTable . '.*');

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

    /**
     * Build Junction instance
     * @param EntityMapper $owner
     * @param EntityMapper $related
     * @return Junction
     */
    protected function buildJunction(EntityMapper $owner, EntityMapper $related): Junction
    {
        return new class ($owner, $related) extends Junction
        {
            /**
             *
             * @param EntityMapper $owner
             * @param EntityMapper $related
             */
            public function __construct(EntityMapper $owner, EntityMapper $related)
            {
                $table = [$owner->getTable(), $related->getTable()];
                sort($table);
                parent::__construct(implode('_', $table), $related->getForeignKey()->columns());
            }
        };
    }
}
