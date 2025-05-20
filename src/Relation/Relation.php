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
 *  @file Relation.php
 *
 *  The Relation class
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

use Platine\Orm\Entity;
use Platine\Orm\EntityManager;
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\EntityMapper;

/**
 * @class Relation
 * @package Platine\Orm\Relation
 * @template TEntity as Entity
 */
abstract class Relation
{
    /**
     * Query callback
     * @var callable|null
     */
    protected $queryCallback = null;

    /**
     * Create new instance
     * @param class-string<TEntity> $entityClass
     * @param ForeignKey|null $foreignKey
     */
    public function __construct(
        protected string $entityClass,
        protected ?ForeignKey $foreignKey = null
    ) {
    }

    /**
     * Set query callback
     * @param callable $callback
     * @return Relation<TEntity>
     */
    public function query(callable $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * @param EntityManager<TEntity> $manager
     * @param EntityMapper<TEntity> $owner
     * @param array<string, mixed> $options the loader options
     *
     * @return mixed
     */
    abstract public function getLoader(
        EntityManager $manager,
        EntityMapper $owner,
        array $options
    ): mixed;

    /**
     * @param DataMapper<TEntity> $mapper
     * @param callable|null $callback
     *
     * @return TEntity|array<TEntity>|null
     */
    abstract public function getResult(
        DataMapper $mapper,
        ?callable $callback = null
    ): Entity|array|null;
}
