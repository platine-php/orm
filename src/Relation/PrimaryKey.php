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
 *  @file PrimaryKey.php
 *
 *  The PrimaryKey class
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
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\Proxy;

/**
 * Class PrimaryKey
 * @package Platine\Orm\Relation
 */
class PrimaryKey
{
    /**
     *
     * @var array<int, string>
     */
    private array $columns = [];

    /**
     * Whether the primary key is composite
     * @var bool
     */
    private bool $composite = false;

    /**
     * Create new instance
     * @param string ...$columns
     */
    public function __construct(string ...$columns)
    {
        $this->columns = $columns;
        $this->composite = count($columns) > 1;
    }

    /**
     *
     * @return array<int, string>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     *
     * @return bool
     */
    public function isComposite(): bool
    {
        return $this->composite;
    }

    /**
     * Return the primary key value(s)
     * @param array<string, mixed> $columns
     * @param bool $map
     *
     * @return mixed|array<string, mixed>|null
     */
    public function getValue(array $columns, bool $map = false)
    {
        if (!$this->composite && !$map) {
            return isset($columns[$this->columns[0]])
                    ? $columns[$this->columns[0]]
                    : null;
        }

        /** @var array<string, mixed> $values */
        $values = [];
        foreach ($this->columns as $column) {
            $values[$column] = isset($columns[$column])
                                ? $columns[$column]
                                : null;
        }

        return $values;
    }

    /**
     * Get the value from data mapper
     * @param DataMapper $mapper
     * @param bool $map
     *
     * @return mixed|array<string, mixed>|null
     */
    public function getValueFromDataMapper(DataMapper $mapper, bool $map = false)
    {
        return $this->getValue($mapper->getRawColumns(), $map);
    }

    /**
     *
     * @param Entity $entity
     * @param bool $map
     * @return mixed|array<string, mixed>|null
     */
    public function getValueFromEntity(Entity $entity, bool $map = false)
    {
        $columns = Proxy::instance()->getEntityColumns($entity);
        return $this->getValue($columns, $map);
    }

    /**
     * The string representation
     * @return string
     */
    public function __toString(): string
    {
        return implode(', ', $this->columns);
    }
}
