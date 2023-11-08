<?php

namespace App\Builders;

use App\Drivers\DBDriver;
use App\Drivers\JsonDriver;

class JsonQueryBuilder extends QueryBuilder
{
    protected $driver = null;

    // instantiate self with matching driver
    public function __construct(string $className)
    {
        parent::__construct($className, JsonDriver::class);
    }

    // atm, no other driver implements count, so the count method is exclusive to JsonDriver and by extent JsonQueryBuilder
    public function count(string $source = '') : ?int
    {
        $this->prepareDriver(DBDriver::QUERY_TYPES['select'], $source);

        $result = $this->driver->count();
        return $result;
    }
}