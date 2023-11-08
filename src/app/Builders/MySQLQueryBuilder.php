<?php

namespace App\Builders;

use App\Drivers\MySQLDriver;
use App\Models\Model;

class MySQLQueryBuilder extends QueryBuilder
{
    protected $driver = null;

    // "find" (retrieve one record based on provided record id) is called "findProduct" in matching driver
    protected $methodAliases = [
        'find' => 'findProduct',
    ];

    // instantiate self with matching driver
    public function __construct(string $className)
    {
        parent::__construct($className, MySQLDriver::class);
    }
}