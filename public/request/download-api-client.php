<?php

$file = base_path('src/RetroAchievementsWebApiClient.php');
$fp = @fopen($file, 'rb');
if (mb_strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
    header('Content-Type: "application/octet-stream"');
    header('Content-Disposition: attachment; filename="RA_API.php"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header("Content-Transfer-Encoding: binary");
    header('Pragma: public');
    header("Content-Length: " . filesize($file));
} else {
    header('Content-Type: "application/octet-stream"');
    header('Content-Disposition: attachment; filename="RA_API.php"');
    header("Content-Transfer-Encoding: binary");
    header('Expires: 0');
    header('Pragma: no-cache');
    header("Content-Length: " . filesize($file));
}
fpassthru($fp);
fclose($fp);
