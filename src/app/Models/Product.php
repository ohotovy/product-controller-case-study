<?php

namespace App\Models;

use App\Drivers\JsonDriver;
use App\Drivers\ElasticSearchDriver;
use App\Drivers\MySQLDriver;
use App\Builders\JsonQueryBuilder;
use App\Builders\QueryBuilder;
use App\Builders\MySQLQueryBuilder;
use App\Builders\ElasticSearchQueryBuilder;

use App\Models\ProductSearch;

use Exception;
use DateTime;

class Product extends Model
{
    // configuration of sources - ElasticSearch, MySQL and Json for caching
    const SOURCES_CONFIG = [
        ElasticSearchDriver::SOURCE_KEY => [
            'builder' => ElasticSearchQueryBuilder::class,
            'table' => 'products',
        ],
        MySQLDriver::SOURCE_KEY => [
            'builder' => MySQLQueryBuilder::class,
            'table' => 'products',
        ],
        JSONDriver::SOURCE_KEY => [
            'builder' => JsonQueryBuilder::class,
            'filepath' => 'cache/db/products/products.json',
        ],
    ];

    // override the standard Model's find() method, which only accesses one specified source with custom logic for product
    public static function find(int $id, string $source = ElasticSearchDriver::SOURCE_KEY) : ?static
    {
        // invalid config key -> switch to default
        $source = key_exists($source, static::SOURCES_CONFIG) ? $source : ElasticSearchDriver::SOURCE_KEY;

        // attempt retrieval from cache - instantiate a JsonQueryBuilder with model Product as argument (to return object of
        // class Product)
        static::$builder = new (static::SOURCES_CONFIG[JSONDriver::SOURCE_KEY]['builder'])(static::class);
        $result = static::$builder->find($id);

        // if not retreived from cache, retrieve from DB (ElasticSearch or MySQL)
        if (is_null($result)) {
            if ($source === ElasticSearchDriver::SOURCE_KEY) {
                // retrieve from ElasticSearch - instantiate a ElasticSearchQueryBuilder with model Product as argument
                static::$builder = new (static::SOURCES_CONFIG[ElasticSearchDriver::SOURCE_KEY]['builder'])(static::class);
            } elseif ($source === MySQLDriver::SOURCE_KEY) {
                // retrieve from MySQL - instantiate a MySQLQueryBuilder with model Product as argument
                static::$builder = new (static::SOURCES_CONFIG[MySQLDriver::SOURCE_KEY]['builder'])(static::class);
            } else {
                throw new Exception("Invalid Source Argument");
            }
            $result = static::$builder->find($id);

            // cache the result, plus change the source to "cache", as the appropriate source ('es'/'sql'/'cache') is stored in
            // all sources' records for better transparency of the demo
            $sourceTemp = $result->source;
            $result->source = 'cache';
            $result->save(JSONDriver::SOURCE_KEY);
            $result->source = $sourceTemp;
        }

        return $result;
    }
}