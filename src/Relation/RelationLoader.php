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
 *  @file RelationLoader.php
 *
 *  The RelationLoader class
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

use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\Proxy;
use Platine\Orm\Query\EntityQuery;

/**
 * @class RelationLoader
 * @package Platine\Orm\Relation
 * @template TEntity as \Platine\Orm\Entity
 */
class RelationLoader
{
    /**
     *
     * @var EntityQuery<TEntity>
     */
    protected EntityQuery $query;

    /**
     *
     * @var bool
     */
    protected bool $inverse = false;

    /**
     *
     * @var ForeignKey
     */
    protected ForeignKey $foreignKey;

    /**
     * Whether it's the has many relation
     * @var bool
     */
    protected bool $hasMany = false;

    /**
     * The result entity query results
     * @var null|TEntity[]
     */
    protected $results = [];

    /**
     *
     * @var array<int, mixed>
     */
    protected array $keys = [];

    /**
     * Create new instance
     * @param EntityQuery<TEntity> $query
     * @param ForeignKey $foreignKey
     * @param bool $inverse
     * @param bool $hasMany
     * @param bool $immediate
     */
    public function __construct(
        EntityQuery $query,
        ForeignKey $foreignKey,
        bool $inverse,
        bool $hasMany,
        bool $immediate
    ) {
        $this->query = $query;
        $this->foreignKey = $foreignKey;
        $this->inverse = $inverse;
        $this->hasMany = $hasMany;

        if ($immediate) {
            $this->loadResults();
        }
    }

    /**
     * Get the result for the given data mapper
     * @param DataMapper<TEntity> $mapper
     * @return null|TEntity|TEntity[]
     */
    public function getResult(DataMapper $mapper)
    {
        $results = $this->loadResults();

        if ($this->inverse) {
            $check = $this->foreignKey->extractValue($mapper->getRawColumns(), true);
        } else {
            $check = $this->foreignKey->getValue($mapper->getRawColumns(), true);
        }

        if ($this->hasMany) {
            $all = [];
            foreach ($this->keys as $index => $item) {
                if ($item === $check) {
                    $all[] = $results[$index];
                }
            }

            return $all;
        }

        foreach ($this->keys as $index => $item) {
            if ($item === $check) {
                return $results[$index];
            }
        }

        return null;
    }

    /**
     * Load the results
     * @return TEntity[]
     */
    protected function loadResults(): array
    {
        if (empty($this->results)) {
            $this->results = $this->query->all();

            $this->keys = [];

            $proxy = Proxy::instance();

            foreach ($this->results as $result) {
                if ($this->inverse) {
                    $this->keys[] = $this->foreignKey->getValue($proxy->getEntityColumns($result), true);
                } else {
                    $this->keys[] = $this->foreignKey->extractValue($proxy->getEntityColumns($result), true);
                }
            }
        }
        return $this->results;
    }
}
