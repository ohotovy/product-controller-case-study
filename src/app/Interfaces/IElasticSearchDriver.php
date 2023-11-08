<?php

namespace App\Interfaces;

interface IElasticSearchDriver
{
    /**
    * @param string $id
    * @return array
    */
    public function findById($id);
}