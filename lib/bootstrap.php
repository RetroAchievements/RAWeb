<?php

define('VERSION', '1.71.0');

if (!file_exists(__DIR__ . '/../.env')) {
    // .env file does not exist - do not attempt to load it nor try connecting to a database
    // helps linter get things done
    return;
}

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

try {
    global $db;
    $db = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), (int) getenv('DB_PORT'));
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
