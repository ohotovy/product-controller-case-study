<?php

namespace App\Builders;

use App\Models\Model;
use App\Drivers\DBDriver;
use App\Drivers\JsonDriver;
use Exception;

/**
 * An intermediary between model and driver.
 *
 * Used as Model::1()->2()->...->m()->n()
 *
 * 1 - actually a model method, not a builder method, determines what needs to be returned (object of class Model, or array of)
 *     and from where (determines driver used, unless changed later).
 * 2-m - chain additional statements for the driver (AND WHERE, OR WHERE, but should be possible to implement others)
 * n - finisher method triggering the data retrieval by driver, calls the driver and makes sure the previously chained are
 *     applied to the query before execution. If specified, can also change the driver used
 */

class QueryBuilder
{
    protected $model = null;
    protected $driver = null;

    protected $statements = [
        'wheres' => [],
    ];

    protected $methodAliases = [];

    public function __construct(string $modelName, string $driver)
    {
        // set a class of Model which should be returned (also used for configuration of source table/file)
        $this->model = $modelName;
        // initiate the driver
        $this->driver = $driver::getInstance();
        // set source table if driver works with DB, or file path if it works with local .json.
        if (method_exists($this->driver,'setTable')) {
            // base source on the model configuration for particular source (e.g. Product is set up to use
            // table `products` in MySQL database)
            $table = $modelName::SOURCES_CONFIG[$driver::SOURCE_KEY]['table'] ?? null;
            if (!is_null($table)) {
                $this->driver->setTable($table);
            }
        } elseif (method_exists($this->driver,'setPath')) {
            $filePath = $modelName::SOURCES_CONFIG[$driver::SOURCE_KEY]['filepath'] ?? null;
            if (!is_null($filePath)) {
                $this->driver->setPath($filePath);
            }
        } else {
            throw new Exception("Source driver {$driver} or model {$modelName} not set up properly", 1);
        }

    }

    // queue AND WHERE statements for later execution on driver
    public function where(string $column, $value) : static
    {
        $this->statements['wheres'][] = ['column' => $column, 'value' => $value, 'isOr' => false];
        return $this;
    }

    // pozn. tohle je tak trochu navíc, spíš jsem si testoval, jestli by to fungovalo a jestli je celý tenhle koncept
    // cca funkční celkově, nejen pro case study (takže jsem v tuhle chvíli neřešil problémy se závorkováním podmínek)
    // queue OR WHERE statements for later execution on driver
    public function orWhere(string $column, $value) : static
    {
        $this->statements['wheres'][] = ['column' => $column, 'value' => $value, 'isOr' => true];
        echo "<br>";
        return $this;
    }

    // finisher statement to retrieve all data matching queued conditions
    public function get(string $source = '') : array
    {
        // see corresponding function for explanation
        $builder = $this;
        $builder = $this->prepareDriver(DBDriver::QUERY_TYPES['select'], $source);

        // call the driver to fetch
        $queryResult = $builder->getDriver()->{$builder->getMethodAliases()['get'] ?? 'get'}();
        // return the results as an array of appropriate models
        $results = [];
        foreach ($queryResult as $record) {
            $results[] = new $this->model($record);
        }
        return $results;
    }

    // finisher statement to find the record with matching ID
    public function find(int $id, string $source = '') : ?Model
    {
        $builder = $this;
        $builder = $this->prepareDriver(DBDriver::QUERY_TYPES['select'], $source);

        $result = $builder->getDriver()->{$builder->getMethodAliases()['find'] ?? 'find'}($id);
        if (!is_array($result)) {
            return null;
        }
        $result = new $this->model($result);
        return $result;
    }

    // finisher statement to save an associative array as a record into a source
    public function save(array $data, string $source = '') : int
    {
        $builder = $this;
        $builder = $this->prepareDriver(DBDriver::QUERY_TYPES['insert'], $source);
        $result = $builder->getDriver()->{$builder->getMethodAliases()['save'] ?? 'save'}($data);
        return $result;
    }

    // instructs the driver to apply all the queued statements on its queue...
    protected function prepareDriver(string $queryType, string $source = '') : self
    {
        // ...and if a source was provided, change the builder used prior to applying the queued statements
        // to a driver matching the provided source
        // example:
        // Product::where(a,b) will initiate an ElasticSearch query builder using ElasticSearchDriver (default builder for Product)
        // Product::where(a,b)->where(c,d) still uses ElasticSearch driver (where doesn't trigger prepareDriver())
        // Product::where(a,b)->where(c,d)->get() will be technically an ElasticSearchQueryBuilder during the get() execution,
        // but it will create and use MySQLQueryBuilder.
        $builder = $this;
        if ($source) {
            if (!(($this->model::SOURCES_CONFIG[$source]['builder']) ?? false)) {
                throw new Exception("Model {$this->model} not configured to use source `{$source}`", 1);
            }
            $builder = (new ($this->model::SOURCES_CONFIG[$source]['builder'])($this->model));
        }
        // apply all the wheres in order (if there were other types of statements implemented, such as where in, like, order by,...
        // they might have their own loops if it is needed for timing of their application to the query string, atm not sure
        // if this would be required)
        foreach ($this->statements['wheres'] as $key => $value) {
            if ($value['isOr']) {
                $builder->driver->orWhere($value['column'],$value['value'],$queryType);
            } else {
                $builder->driver->where($value['column'],$value['value'],$queryType);
            }
        }
        return $builder;
    }

    // allow access to driver from a builder of different class for the purpose of switching driver during finisher statements
    public function getDriver() : DBDriver|JsonDriver
    {
        return $this->driver;
    }

    // allow access to method aliases from a builder of different class for the purpose of switching driver during finisher statements
    public function getMethodAliases() : array
    {
        return $this->methodAliases ?? [];
    }
}