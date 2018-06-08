<?php
echo json_encode(['Success' => false, 'Error' => 'Deprecated']);
return false;

// require_once __DIR__ . '/../lib/bootstrap.php';
//
// if (!ValidatePOSTChars("utg")) {
//     echo "FAILED";
//     return;
// }
//
// $user = seekPOST('u');
// $token = seekPOST('t');
// $gameID = seekPOST('g');
// //settype( $gameID, 'integer' );
// $hardcoreMode = seekPOST('h', 0);
//
// if (validateUser_app($user, $token, $fbUser, 0) == true) {
//     echo "OK:";
//
//     $numUnlocks = getUserUnlocks($user, $gameID, $dataOut, $hardcoreMode);
//     for ($i = 0; $i < $numUnlocks; $i++) {
//         echo $dataOut[$i] . ":";
//     }
//
//     return true;
// }
//
// echo "FAILED: Invalid User/Password combination.\n";// . $user . "\n" . $pass . "\n" . $hashed . "\n";
// return false;
