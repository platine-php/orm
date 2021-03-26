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
 *  @file Junction.php
 *
 *  The Junction class
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
 * Class Junction
 * @package Platine\Orm\Relation
 */
class Junction
{
    /**
     *
     * @var array<string, string>
     */
    private array $columns = [];

    /**
     * The name of junction table
     * @var string
     */
    private string $table;

    /**
     * Create new instance
     * @param string $table
     * @param array<string, string> $columns
     */
    public function __construct(string $table, array $columns)
    {
        $this->table = $table;
        $this->columns = $columns;
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
     * @return string
     */
    public function table(): string
    {
        return $this->table;
    }
}
