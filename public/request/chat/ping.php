<?php
return;
// require_once __DIR__ . '/../../../lib/bootstrap.php';
//
// // retrieve the operation to be performed
// $mode = $_POST['mode'];
//
// // default the last id to 0
// $id = 0;
//
// //	default max messages to return
// $maxMessages = 50;
//
// // create a new Chat instance
// $chat = new \RA\Chat();
//
// // if the operation is SendAndRetrieve
// if ($mode == 'SendAndRetrieveNew') {
//     // retrieve the action parameters used to add a new message
//     $name = $_POST['name'];
//     $message = urldecode($_POST['message']);
//     $id = $_POST['id'];
//
//     // check if we have valid values
//     if ($name != '' && $message != '') {
//         // post the message to the database
//         $chat->postMessage($name, $message);
//         userActivityPing($name);
//     }
// } else {
//     if ($mode == 'RetrieveNew') {
//         // if the operation is Retrieve
//         // get the id of the last message retrieved by the client
//         $id = $_POST['id'];
//         $maxMessages = $_POST['maxmsg'];
//     }
// }
// // Clear the output
// if (ob_get_length()) {
//     ob_clean();
// }
//
// // Headers are sent to prevent browsers from caching
// header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
// header('Cache-Control: no-cache, must-revalidate');
// header('Pragma: no-cache');
// header('Content-Type: text/xml');
//
// // retrieve new messages from the server
// echo $chat->retrieveNewMessages($id, $maxMessages);
