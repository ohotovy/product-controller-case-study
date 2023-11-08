<?php

namespace App\Drivers;

use App\Traits\IsSingleton;
use App\Interfaces\IElasticSearchDriver;
use Dotenv\Dotenv;

class ElasticSearchDriver extends DBDriver implements IElasticSearchDriver
{
    use IsSingleton;

    protected $connection = null;
    protected $database = null;

    const SOURCE_KEY = 'es';

    // set the database to the one used to emulate ElasticSearch
    private function __construct()
    {
        $this->database = $_ENV['ES_DATABASE_NAME'];
    }

    // the method Logio drivers have, in this context serves as a mask to DBDriver::find() which retrieves the data
    // from the dummy database
    public function findById($id) : ?array
    {
        $result = $this->find($id);

        return $result;
    }
}