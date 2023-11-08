<?php

$env = parse_ini_file('.env');

$table = "products";

foreach (['sql','es'] as $database) {
    try {
        $db = new PDO("mysql:host=db;dbname={$database}_products", "root", $env['DB_PASSWORD'] );
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $query ="CREATE table $table(
        id INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR( 50 ) NOT NULL,
        source VARCHAR( 3 ) NOT NULL);" ;
        $db->exec($query);
        print("Created {$table} Table in {$database}_products.\n");

        for ($i = 1; $i <= 4 ; $i++) {
            $query = "INSERT INTO {$table} (`name`, `source`)
            VALUES ('Product {$i} {$database}', '{$database}')";
            $db->exec($query);
        }
        print("Populated {$table} Table in {$database}_products.\n");

    } catch(PDOException $e) {
        echo $e->getMessage();
    }
}