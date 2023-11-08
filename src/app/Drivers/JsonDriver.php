<?php

namespace App\Drivers;

use App\Traits\IsSingleton;

class JsonDriver
{
    use IsSingleton;

    protected $projectRoot = __DIR__."/../../../";
    protected $path = '';

    const SOURCE_KEY = 'json';

    protected $conditions = [];

    // get all records from a .json file, limited by potential conditions
    public function get() : array
    {
        // if the file exists, read it, if not, create it with empty array
        if (file_exists($this->projectRoot.$this->path)) {
            $json = file_get_contents($this->projectRoot.$this->path);
        } else {
            $json = "[]";
            file_put_contents($this->projectRoot.$this->path, $json);
        }

        // retrieve all records from .json file
        $array = json_decode($json, true);

        // filter results based on conditions accumulated by where() methods called prior to retrieval
        $array = array_filter($array, function ($product) {
            $meetsConditions = true;
            foreach ($this->conditions as $key => $value) {
                $meetsConditions = $meetsConditions && ($product[$key] ?? null) === $value;
            }
            return $meetsConditions;
        });

        // clear the conditions for next use of the singleton
        $this->conditions = [];

        // return reindexed results
        return array_values($array);
    }

    // queue where equals conditions for get() method
    public function where(string $key, $value) : static
    {
        $this->conditions[$key] = $value;
        return $this;
    }

    // retrieve only first result
    public function first() : ?array
    {
        $array = $this->get();
        return $array[0] ?? null;
    }

    // find a record based on ID
    public function find($id) : ?array
    {
        return $this->where('id',$id)->first();
    }

    // save record at the end of file
    public function save(array $record) : int
    {
        // get all records
        $records = $this->get();
        // if the record doesn't have an ID (e.g. when saving a record about search)
        // determine it as the last saved ID + 1
        if (!($record['id'] ?? false)) {
            $maxId = 0;
            foreach ($records as $existingRecord) {
                if ($existingRecord['id'] > $maxId) {
                    $maxId = $existingRecord['id'];
                }
            }
            $record['id'] = $maxId + 1;
        }
        // put new record at the end of array and save
        $records[] = $record;
        file_put_contents($this->projectRoot.$this->path,json_encode($records));
        return $record['id'];
    }

    // count records matching queued conditions (for number of product searches)
    public function count() : int
    {
        $records = $this->get();
        return count($records);
    }

    // external method for setting a file path by builder
    public function setPath(string $path) : void
    {
        $this->path = $path;
    }
}