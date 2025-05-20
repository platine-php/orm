<?php

declare(strict_types=1);

namespace Platine\Test\Fixture\Orm;

use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\Generator;
use Platine\Database\Connection as RealConnection;
use Platine\Database\ResultSet;
use Platine\Orm\Entity;
use Platine\Orm\Mapper\EntityMapperInterface;

class MyEntity extends Entity
{
    public static function mapEntity(EntityMapperInterface $mapper): void
    {
        $mapper->relation('relation')->belongsTo(MyEntity::class);
    }
}

/**
* Class to mock PDO to prevent error:
* You cannot serialize or unserialize PDO instances
*/
class PDOMock extends PDO
{
    /**
    * Disable the default constructor
    */
    public function __construct()
    {
    }

    public function lastInsertId($seq = null)
    {
        return 1;
    }
}


class Connection extends RealConnection
{
    private string $rawSql = '';

    public function __construct($driver)
    {
        $driverClass = '\\Platine\\Database\\Driver\\' . $driver;
        $this->driver = new $driverClass($this);
        $this->pdo = new PDOMock();
    }

    public function getRawSql(): string
    {
        return $this->rawSql;
    }

    public function query(string $sql, array $params = []): ResultSet
    {
        $this->rawSql = $this->replaceParameters($sql, $params);

        $stmt = (new Generator())->getMock(PDOStatement::class);

        return new ResultSet($stmt);
    }

    public function column(string $sql, array $params = []): mixed
    {
        $this->rawSql =  $this->replaceParameters($sql, $params);

        return 1;
    }

    public function count(string $sql, array $params = []): int
    {
        $this->rawSql =  $this->replaceParameters($sql, $params);

        return 0;
    }

    public function exec(string $sql, array $params = []): bool
    {
        $this->rawSql = $this->replaceParameters($sql, $params);

        return true;
    }

    public function transaction(
        callable $callback,
        $that = null
    ): mixed {
        if ($that === null) {
            $that = $this;
        }

        return $callback($that);
    }
}
