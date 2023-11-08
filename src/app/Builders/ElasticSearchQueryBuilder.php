<?php

namespace App\Builders;

use App\Drivers\ElasticSearchDriver;
use App\Models\Model;

class ElasticSearchQueryBuilder extends QueryBuilder
{
    protected $driver = null;

    // "find" (retrieve one record based on provided record id) is called "findById" in matching driver
    protected $methodAliases = [
        'find' => 'findById',
    ];

    // instantiate self with matching driver
    public function __construct(string $className)
    {
        parent::__construct($className,ElasticSearchDriver::class);
    }
}