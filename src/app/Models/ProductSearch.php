<?php

namespace App\Models;

use App\Drivers\JsonDriver;
use App\Drivers\ElasticSearchDriver;
use App\Drivers\MySQLDriver;
use App\Builders\JsonQueryBuilder;
use App\Builders\QueryBuilder;
use App\Builders\MySQLQueryBuilder;
use App\Builders\ElasticSearchQueryBuilder;

use Exception;

class ProductSearch extends Model
{
    // This model only contains configurations for its sources, as unlike Product, it doesn't have any
    // non-standard methods
    const SOURCES_CONFIG = [
        JSONDriver::SOURCE_KEY => [
            'builder' => JsonQueryBuilder::class,
            'filepath' => 'storage/data/productSearch.json',
        ],
        MySQLDriver::SOURCE_KEY => [
            'builder' => MySQLQueryBuilder::class,
            'table' => 'product_searches',
        ],
        ElasticSearchDriver::SOURCE_KEY => [
            'builder' => ElasticSearchQueryBuilder::class,
            'table' => 'product_searches',
        ],
    ];
}