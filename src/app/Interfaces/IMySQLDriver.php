<?php

namespace App\Interfaces;

interface IMySQLDriver
{
    /**
    * @param string $id
    * @return array
    */
    public function findProduct($id);
}
