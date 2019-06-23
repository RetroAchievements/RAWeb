<?php
echo json_encode(['Success' => false, 'Error' => 'Deprecated']);
return false;

// require_once __DIR__ . '/../lib/bootstrap.php';
//
// //	Sanitise!
// if (!ValidatePOSTChars("utgan")) {
//     echo "FAILED";
//     return;
// }
//
// $user = $_POST["u"];
// $token = $_POST["t"];
// $note = $_POST["n"];
// $address = $_POST["a"];
//
// $gameID = $_POST["g"];
// settype($gameID, 'integer');
//
// //	User privelidges to submit a code note:
// if (validateUser_app($user, $token, $fbUser, 1)) {
//     if (submitCodeNote($user, $gameID, $address, $note)) {
//         echo "OK";
//     } else {
//         echo "FAILED!";
//     }
// } else {
//     echo "FAILED! Cannot validate $user. Have you confirmed your email?";
// }
