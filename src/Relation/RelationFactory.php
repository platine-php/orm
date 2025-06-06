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
 *  @file RelationFactory.php
 *
 *  The RelationFactory class
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

use Closure;

/**
 * @class RelationFactory
 * @package Platine\Orm\Relation
 * @template TEntity as \Platine\Orm\Entity
 */
class RelationFactory
{
    /**
     * Create new instance
     * @param string $name
     * @param Closure $callback
     */
    public function __construct(protected string $name, protected Closure $callback)
    {
    }

    /**
     * Set has one relation
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation<TEntity>
     */
    public function hasOne(string $entityClass, ?ForeignKey $foreignKey = null): Relation
    {
        return ($this->callback)($this->name, new HasOne($entityClass, $foreignKey));
    }

    /**
     * Set has many relation
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation<TEntity>
     */
    public function hasMany(string $entityClass, ?ForeignKey $foreignKey = null): Relation
    {
        return ($this->callback)($this->name, new HasMany($entityClass, $foreignKey));
    }

    /**
     * Set belongs to relation
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     * @return Relation<TEntity>
     */
    public function belongsTo(string $entityClass, ?ForeignKey $foreignKey = null): Relation
    {
        return ($this->callback)($this->name, new BelongsTo($entityClass, $foreignKey));
    }

    /**
     * Set share one relation
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     * @param Junction|null $junction
     * @return Relation<TEntity>
     */
    public function shareOne(
        string $entityClass,
        ?ForeignKey $foreignKey = null,
        ?Junction $junction = null
    ): Relation {
        return ($this->callback)($this->name, new ShareOne(
            $entityClass,
            $foreignKey,
            $junction
        ));
    }

    /**
     * Set share many relation
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     * @param Junction|null $junction
     * @return Relation<TEntity>
     */
    public function shareMany(
        string $entityClass,
        ?ForeignKey $foreignKey = null,
        ?Junction $junction = null
    ): Relation {
        return ($this->callback)($this->name, new ShareMany(
            $entityClass,
            $foreignKey,
            $junction
        ));
    }
}
