<?php
require_once __DIR__ . '/../lib/bootstrap.php';

$date = date('Y/m/d H:i:s');
echo $date . "\r\n";

//$output = shell_exec('crontab -l');
//echo "<pre>$output</pre>";
//error_log( "Cron Job Run!" );
