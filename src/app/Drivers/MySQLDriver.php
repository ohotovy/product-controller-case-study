<?php

namespace App\Drivers;

use App\Traits\IsSingleton;
use App\Interfaces\IMySQLDriver;

class MySQLDriver extends DBDriver implements IMySQLDriver
{
    use IsSingleton;

    protected $connection = null;
    protected $database = null;

    const SOURCE_KEY = 'sql';

    // set the database to the one used to emulate SQL DB
    private function __construct()
    {
        $this->database = $_ENV['SQL_DATABASE_NAME'];
    }

    // the method Logio drivers have, in this context serves as a mask to DBDriver::find() which retrieves the data
    // from the dummy database
    public function findProduct($id) : ?array
    {
        $result = $this->find($id);

        return $result;
    }
}