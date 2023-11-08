<?php

namespace App\Drivers;

use Dotenv\Dotenv;
use PDO;
use PDOException;

// pozn. Tato třída je v podstatě jen společný základ ElasticSearch a MySQL driverů, vzhledem k tomu,
// že ElasticSearch neznám a nemám, a pro účely úkolu jsem ji vytvořil jako druhou, oddělenou SQL databázi

abstract class DBDriver
{
    const QUERY_TYPES = [
        'select' => 'select',
        'insert' => 'insert',
    ];

    protected $initialQueries = [
        'select' => "SELECT * FROM `**TABLE_NAME**` WHERE 1=1**WHERES_END**",
        // pozn. insert klíč se používá jen pro demo změny ukládání informací o hledání produktů,
        // který se vyskytuje v QueryBuilder::save(), ale fakticky jediný skutečný zápis probíhá v JsonDriver,
        // a DBDriver::save() skutečnou query nevyužívá
        'insert' => "DUMMY",
    ];

    // connection PDO object
    protected $connection = null;
    // database name string
    protected $database = null;
    // table name string
    protected $table = null;
    // prepared query string
    protected $query = null;
    // array of parameters for PDO statement
    protected $parameters = [];
    // markers used in query to denote where to add various statements (currently only WHERE statements)
    // can be added, but should be possible to expand on this logic
    protected $queryMarkers = [
        '**WHERES_END**',
    ];

    protected function init(string $queryType) : void
    {
        // if connection doesn't exist, create it
        if (!$this->connection instanceof PDO) {
            $this->connection = new PDO("mysql:host=db;dbname={$this->database}",'root',$_ENV['DB_PASSWORD']);
        }
        // if query doesn't exist, initiate it as the most basic query of type (select, insert,...)
        if (gettype($this->query) !== 'string') {
            $query = $this->initialQueries[$queryType];
            $query = str_replace('**TABLE_NAME**',$this->table, $query);
            $this->query = $query;
        }
    }

    // before executing query, remove markers used for inserting statements such as "id = ?" into correct position
    // in query string
    protected function removeMarkers() : void
    {
        foreach ($this->queryMarkers as $marker) {
            $this->query = str_replace($marker,'',$this->query);
        }
    }

    // when query is executed, clear singleton parameters related to one particular query
    protected function clearQuery() : void
    {
        $this->table = null;
        $this->query = null;
        $this->parameters = [];
    }

    // retrieve all data (fulfilling eventual previous conditions)
    public function get() : array
    {
        try {
            // initialize as select query
            $this->init(static::QUERY_TYPES['select']);
            // remove markers from the query
            $this->removeMarkers();

            // fetch
            $stmt = $this->connection->prepare($this->query);
            $stmt->execute($this->parameters);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // clear query for another use
            $this->clearQuery();
        } catch (PDOException $e) {
            // clear query for another use
            $this->clearQuery();
            throw new PDOException($e->getMessage());
        }

        return $result;
    }

    // retrieve first result (fulfilling eventual previous conditions)
    public function first() : ?array
    {
        // as get...
        try {
            $this->init(static::QUERY_TYPES['select']);
            $this->removeMarkers();

            $stmt = $this->connection->prepare($this->query);
            $stmt->execute($this->parameters);
            // ...except don't fetchAll, just fetch first
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->clearQuery();
        } catch (PDOException $e) {
            $this->clearQuery();
            throw new PDOException($e->getMessage());
        }

        return $result ? $result : null;
    }

    // queue "AND WHERE x=y" statements
    public function where(string $column, mixed $value, string $queryType) : static
    {
        // initialize as a query of appropriate type (determined by builder)
        $this->init($queryType);
        // adjust the query string
        $i = 0;
        $valueId = $column;
        while (array_key_exists($valueId, $this->parameters)) {
            $valueId = $valueId.$i;
        }
        $this->query = str_replace("**WHERES_END**"," AND `{$column}` = :{$valueId}**WHERES_END**", $this->query);
        // adjust the parameters array
        $this->parameters[$valueId] = $value;
        return $this;
    }

    // queue "OR WHERE x=y" statements
    public function orWhere(string $column, mixed $value, string $queryType) : static
    {
        // as where()
        $this->init($queryType);
        $i = 0;
        $valueId = $column;
        while (array_key_exists($valueId, $this->parameters)) {
            $valueId = $valueId.$i;
        }
        $this->query = str_replace("**WHERES_END**"," OR `{$column}` = :{$valueId}**WHERES_END**", $this->query);
        $this->parameters[$valueId] = $value;
        return $this;
    }

    public function find(int $id) : ?array
    {
        return $this->where('id',$id, self::QUERY_TYPES['select'])->first();
    }

    // external method for setting a table, used by builder
    public function setTable(string $table) : void
    {
        $this->table = $table;
    }

    // dummy function for demonstrating the switch from saving the product search data as .json to saving it
    // using e.g. MySQL database
    public function save(array $record) : int
    {
        $className = static::class;
        $tableName = $this->table;
        echo <<<TEXT
        <pre>
        Using {$className} to store record
        [
            id => AUTOINCREMENT
            product_id => {$record['product_id']}
            timestamp => {$record['timestamp']}
        ]
        into table {$tableName}
        ----------------------------------------
        </pre>
        TEXT;
        return 1;
    }
}