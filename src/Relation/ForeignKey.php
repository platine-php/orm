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
 *  @file ForeignKey.php
 *
 *  The ForeignKey class
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

/**
 * Class ForeignKey
 * @package Platine\Orm\Relation
 */
class ForeignKey
{
    /**
     *
     * @var array<string, string>
     */
    private array $columns = [];

    /**
     * Whether the foreign key is composite
     * @var bool
     */
    private bool $composite = false;

    /**
     * Create new instance
     * @param array<string, string> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
        $this->composite = count($columns) > 1;
    }

    /**
     *
     * @return array<string, string>
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
     * Return the foreign key value(s)
     * @param array<string, mixed> $columns
     * @param bool $map
     *
     * @return mixed|array<string, mixed>|null
     */
    public function getValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            $column = array_keys($this->columns);

            return isset($columns[$column[0]])
                    ? $columns[$column[0]]
                    : null;
        }

        $values = [];
        foreach ($this->columns as $candidate => $column) {
            $values[$column] = isset($columns[$candidate])
                                ? $columns[$candidate]
                                : null;
        }

        return $values;
    }

    /**
     * Return the foreign key inverse value(s)
     * @param array<string, mixed> $columns
     * @param bool $map
     *
     * @return mixed|array<string, mixed>|null
     */
    public function getInverseValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            $column = array_values($this->columns);

            return isset($columns[$column[0]])
                    ? $columns[$column[0]]
                    : null;
        }

        $values = [];
        foreach ($this->columns as $candidate => $column) {
            $values[$candidate] = isset($columns[$column])
                                ? $columns[$column]
                                : null;
        }

        return $values;
    }

    /**
     * Extract the foreign key value(s)
     * @param array<string, mixed> $columns
     * @param bool $map
     *
     * @return mixed|array<string, mixed>|null
     */
    public function extractValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            $column = array_values($this->columns);

            return isset($columns[$column[0]])
                    ? $columns[$column[0]]
                    : null;
        }

        $values = [];
        foreach ($this->columns as $column) {
            $values[$column] = isset($columns[$column])
                                ? $columns[$column]
                                : null;
        }

        return $values;
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
