<?php

namespace App\Models;

use App\Builders\MySQLQueryBuilder;
use App\Builders\QueryBuilder;
use App\Drivers\MySQLDriver;
use Exception;

/**
 * Class representing a record in dynamic context, while in static context it serves as an initator for the data retrieval
 * as it tells builder what needs to be retrieved (and from where, unless changed later in chain of builders)
 */

abstract class Model
{
    // default driver and builder classes
    protected static $driverClass = MySQLDriver::class;
    protected static $builderClass = MySQLQueryBuilder::class;

    // holds currently active builder
    public static $builder = null;

    // holds data
    protected $data = [];

    // allows creation of a model from array
    public function __construct(array $dataArray)
    {
        $this->data = $dataArray;
    }

    // initiates a builder when data retrieval is started by calling one of the methods statically on model
    protected static function init(string $source = '') : void
    {
        // select a source configuration - either by specifiying source, or setting the default (first source in SOURCES_CONFIG).
        $config = static::SOURCES_CONFIG[$source] ?? array_values(static::SOURCES_CONFIG)[0] ?? false;
        // The SOURCES_CONFIG must exist for each particular model, as even if builder and driver can be defaulted to Model class
        // values, each model must have its source table/file path defined
        if (!$config) {
            throw new Exception("Incorrect configuration for model {static::class}, SOURCES_CONFIG missing", 1);
        }
        $builderClass = $config['builder'] ?? static::$builderClass;
        $driverClass = $config['driver'] ?? static::$driverClass;
        // instantiate a builder
        static::$builder = new $builderClass(static::class, $driverClass);
    }

    public static function get(string $source = '') : array
    {
        static::init($source);
        $result = static::$builder->get();
        return $result;
    }

    // all following methods instantiate a builder and call its corresponding method (and return either
    // result or builder, based on whether it's a chain method, or finisher method actually triggering the retrieval)
    public static function find(int $id, string $source = '') : ?static
    {
        static::init($source);
        $result = static::$builder->find($id);
        return $result;
    }

    public static function count(string $source = '') : ?int
    {
        static::init($source);
        $result = static::$builder->count();
        return $result;
    }

    public static function where(string $column, $value) : QueryBuilder
    {
        static::init();
        $builder = static::$builder->where($column, $value);
        return $builder;
    }

    public static function orWhere(string $column, $value) : QueryBuilder
    {
        trigger_error("using orWhere as a first query statement results in \"WHERE (1=1 OR condition)...\", invalidating the rest of the query. ", E_USER_WARNING);
        static::init();
        $builder = static::$builder->orWhere($column, $value);
        return $builder;
    }

    public function save($source = '') : int
    {
        static::init($source);
        $result = static::$builder->save($this->data);
        return $result;
    }

    // allow access to the data array
    public function getData() : array
    {
        return $this->data;
    }

    // allow access to data array values as object properties
    public function __get(string $key) : mixed
    {
        return $this->data[$key];
    }

    public function __set(string $key, mixed $value) : void
    {
        $this->data[$key] = $value;
    }

    // allow echoing the object directly as a JSON of its data
    public function __toString() : string
    {
        return json_encode($this->data);
    }
}