<?php

// enable classes autoload
require __DIR__.'/../vendor/autoload.php';

use App\Controllers\ProductController;
use Dotenv\Dotenv;

// enable usage of $_ENV with local entries
$dotenv = Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();

// determine parameters for the search - product being searched for and optionally source (defaults to ElasticSearch)
$productId = (int) ($_GET["product"] ?? 1);
$source = $_GET['source'] ?? 'es';

echo "<pre>";
// initiate controller and echo detail method result;
$controller = \App\Controllers\ProductController::getInstance();
echo $controller->detail($productId, $source);

echo "</pre>";