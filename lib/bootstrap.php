<?php

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

define("VERSION", "1.67.0");

try {
    global $db;
    $db = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), getenv('DB_PORT'));
    if (!$db) {
        throw new Exception('Could not connect to database. Please try again later.');
    }
    mysqli_set_charset($db, 'latin1');
    mysqli_query($db, "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
} catch (Exception $exception) {
    if (getenv('APP_ENV') === 'local') {
        throw $exception;
    } else {
        echo 'Could not connect to database. Please try again later.';
        exit;
    }
}
