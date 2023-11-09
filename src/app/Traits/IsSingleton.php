<?php

namespace App\Traits;

trait IsSingleton
{
    private function __construct()
    {

    }

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
}