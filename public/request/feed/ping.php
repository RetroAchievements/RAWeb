<?php

echo '<response></response>';
return;
// require_once __DIR__ . '/../../../vendor/autoload.php';
// require_once __DIR__ . '/../../../lib/bootstrap.php';
// // retrieve the operation to be performed
// $mode = $_POST['mode'];
//
// // default the last id to 0
// $id = 0;
// // create a new Feed instance
// $feed = new \RA\Feed();
//
// if (!isset($mode)) {
//     error_log("No mode supplied (feed)");
//     //return;
//     $mode = "unknown";
// }
//
// $user = null;
//
// if ($mode == 'RetrieveNew') {
//     // get the id of the last message retrieved by the client
//     $id = $_POST['id'];
//     $user = isset($_POST['user']) ? $_POST['user'] : null;
// }
//
// // Clear the output
// if (ob_get_length()) {
//     ob_clean();
// }
// // Headers are sent to prevent browsers from caching
// header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
// header('Cache-Control: no-cache, must-revalidate');
// header('Pragma: no-cache');
// header('Content-Type: text/xml');
// // retrieve new messages from the server
// echo $feed->retrieveNewMessages($id, $user);
