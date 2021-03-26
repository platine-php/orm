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
 *  @file EntityMapperInterface.php
 *
 *  The Entity Mapper Interface
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

use Platine\Orm\Relation\RelationFactory;

/**
 * Class EntityMapperInterface
 * @package Platine\Orm\Mapper
 */
interface EntityMapperInterface
{
    /**
     * The name of the entity
     * @param string $name
     * @return $this
     */
    public function name(string $name): self;

    /**
     * The name of the associated table
     * @param string $table
     * @return $this
     */
    public function table(string $table): self;

    /**
     * The name or list of primary key name
     * @param string ...$primaryKey
     * @return $this
     */
    public function primaryKey(string ...$primaryKey): self;

    /**
     * The callback to generate the primary key
     * Note: this callback must have a signature
     *  function(DataMapper $data){
     *
     *  }
     * The return value will be used as primary key value.
     * If the primary key is the composite return array of primary key values
     * array(
     *   'column_1' => 'value_1',
     *   'column_2' => 'value_2',
     *   ...
     *   'column_n' => 'value_n'
     * )
     * @param callable $generator
     * @return $this
     */
    public function primaryKeyGenerator(callable $generator): self;

    /**
     * The name of the sequence
     * @param string $sequence
     * @return $this
     */
    public function sequence(string $sequence): self;

    /**
     * The column getter callback
     * Note: this callback must have a signature
     *  function(mixed $value, DataMapper $data){
     *
     *  }
     * @param string $column
     * @param callable $getter
     * @return $this
     */
    public function getter(string $column, callable $getter): self;

    /**
     * The column setter callback
     * Note: this callback must have a signature
     *  function(mixed $value, DataMapper $data){
     *
     *  }
     * @param string $column
     * @param callable $setter
     * @return $this
     */
    public function setter(string $column, callable $setter): self;

    /**
     * Set the relation between Entity
     * @param string $name
     * @return RelationFactory
     */
    public function relation(string $name): RelationFactory;

    /**
     * Set each columns the type for get and set
     * @param array<string, string> $columns
     * @return $this
     */
    public function casts(array $columns): self;

    /**
     * Use softDelete functionality
     * @param bool $value
     * @param string $column the name of column to use
     * Note: $column Must cast to '?date'
     * @return $this
     */
    public function useSoftDelete(
        bool $value = true,
        string $column = 'deleted_at'
    ): self;

    /**
     * Use timestamp functionality
     * @param bool $value
     * @param string $createdAt
     * @param string $updatedAt
     *
     * Note: $updatedAt must cast to '?date'
     * @return $this
     */
    public function useTimestamp(
        bool $value = true,
        string $createdAt = 'created_at',
        string $updatedAt = 'updated_at'
    ): self;

    /**
     * List of column to accept mass assignement
     * @param array<int, string> $columns
     * @return $this
     */
    public function fillable(array $columns): self;

    /**
     * List of column to be guarded
     * @param array<int, string> $columns
     * @return $this
     */
    public function guarded(array $columns): self;


    /**
     * Define the filter to use in entity query
     * Note: this callback must have a signature
     *  function(Platine\Orm\Query\Query $query, mixed $args){
     *
     *  }
     * @param string $name
     * @param callable $filter
     * @return $this
     */
    public function filter(string $name, callable $filter): self;


    /**
     * Subscribe to the event during entity life cycle like "insert", "update", "delete", etc.
     * Note: this callback must have a signature
     *  function(Entity $entity, DataMapper $mapper){
     *
     *  }
     * @param string $name
     * @param callable $handler
     * @return $this
     */
    public function on(string $name, callable $handler): self;
}
