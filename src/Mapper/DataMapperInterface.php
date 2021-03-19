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
 *  @file DataMapperInterface.php
 *
 *  The Data Mapper Interface
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

use Platine\Orm\Entity;

/**
 * Class DataMapperInterface
 * @package Platine\Orm\Mapper
 */
interface DataMapperInterface
{

    /**
     * Whether the record is new
     * @return bool
     */
    public function isNew(): bool;

    /**
     * Whether the record is read only
     * @return bool
     */
    public function isReadOnly(): bool;

    /**
     * Whether the record is deleted
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * Whether the record was modified
     * @return bool
     */
    public function wasModified(): bool;

    /**
     * Whether the entity has the given column
     * @param string $column
     * @return bool
     */
    public function hasColumn(string $column): bool;

    /**
     * Whether the entity has the given related
     * @param string $relation
     * @return bool
     */
    public function hasRelation(string $relation): bool;

    /**
     * Return the raw columns data
     *
     * @return array<string, mixed>
     */
    public function getRawColumns(): array;

    /**
     * Return the modified columns data
     *
     * @return array<int, string>
     */
    public function getModifiedColumns(): array;

    /**
     * Return the value for the given column
     * @param string $name
     *
     * @return mixed
     */
    public function getColumn(string $name);

    /**
     * Set the value for the given column
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function setColumn(string $name, $value): void;

    /**
     * Clear the value for the given column
     * @param string $name
     *
     * @return void
     */
    public function clearColumn(string $name): void;

    /**
     * Set the raw value for the given column
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function setRawColumn(string $name, $value): void;

    /**
     * Return the value for the given relation
     * @param string $name
     * @param callable|null $callback
     *
     * @return mixed
     */
    public function getRelated(string $name, ?callable $callback = null);

    /**
     * Set the value for the given relation
     * @param string $name
     * @param Entity|null $entity
     *
     * @return void
     */
    public function setRelated(string $name, ?Entity $entity = null): void;

    /**
     * Clear the given relation data
     * @param string $name
     * @param bool $loaders
     * @return void
     */
    public function clearRelated(string $name, bool $loaders = false): void;

    /**
     * Make a link between for the given relation and entity
     * @param string $relation
     * @param Entity $entity
     * @return void
     */
    public function link(string $relation, Entity $entity): void;

    /**
     * Remove the link between for the given relation and entity
     * @param string $relation
     * @param Entity $entity
     * @return void
     */
    public function unlink(string $relation, Entity $entity): void;


    /**
     * Fill the entity information using the given columns data
     * @param array<string, mixed> $columns
     * @return void
     */
    public function fill(array $columns): void;


    /**
     * Reload the entity data from data source
     * @return void
     */
    public function refresh(): void;
}
