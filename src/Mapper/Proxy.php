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
 *  @file Proxy.php
 *
 *  The Proxy class
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
use Platine\Orm\Mapper\DataMapper;
use Platine\Orm\Mapper\Proxy;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

/**
 * Class Proxy
 * @package Platine\Orm\Mapper
 */
class Proxy
{

    /**
     * The Entity data mapper arguments property
     * @var ReflectionProperty
     */
    private ReflectionProperty $dataMapperArgs;

    /**
     * The Entity mapper() method
     * @var ReflectionMethod
     */
    private ReflectionMethod $mapperMethod;

    /**
     * Create new instance
     * @throws ReflectionException
     */
    private function __construct()
    {
        $reflection = new ReflectionClass(Entity::class);

        $this->dataMapperArgs = $reflection->getProperty('dataMapperArgs');
        $this->mapperMethod = $reflection->getMethod('mapper');

        $this->dataMapperArgs->setAccessible(true);
        $this->mapperMethod->setAccessible(true);
    }

    /**
     * Get the data mapper instance for the given entity
     * @param Entity $entity
     * @return DataMapper
     */
    public function getEntityDataMapper(Entity $entity): DataMapper
    {
        return $this->mapperMethod->invoke($entity);
    }

    /**
     * Return the columns list
     * @param Entity $entity
     * @return array<string, mixed>
     */
    public function getEntityColumns(Entity $entity): array
    {
        $value = $this->dataMapperArgs->getValue($entity);

        if ($value !== null) {
            return $value[2];
        }
        //Race condition
        //@codeCoverageIgnoreStart
        return $this->getEntityDataMapper($entity)->getRawColumns();
        //@codeCoverageIgnoreEnd
    }

    public static function instance(): Proxy
    {
        static $proxy = null;

        if ($proxy === null) {
            //Race condition
            //@codeCoverageIgnoreStart
            try {
                $proxy = new self();
            } catch (ReflectionException $exception) {
                throw new RuntimeException(
                    sprintf(
                        'Can not make instance of [%s] check if the entity class exists',
                        Proxy::class
                    ),
                    (int) $exception->getCode(),
                    $exception->getPrevious()
                );
            }
            //@codeCoverageIgnoreEnd
        }

        return $proxy;
    }
}
