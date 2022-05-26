<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

echo count(getCurrentlyOnlinePlayers()) . PHP_EOL;
