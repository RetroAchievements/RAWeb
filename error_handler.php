<?php
// set the user error handler method to be error_handler
set_error_handler('error_handler', E_ALL);
// error handler function
function error_handler($errNo, $errStr, $errFile, $errLine)
{
  // clear any output that has already been generated
  if(ob_get_length()) ob_clean();
  // output the error message 
  $error_message = 'ERRNO: ' . $errNo . chr(10) .
                   'TEXT: ' . $errStr . chr(10) .
                   'LOCATION: ' . $errFile . 
                   ', line ' . $errLine;
  echo $error_message;
  // prevent processing any more PHP scripts
  exit;
}
?>
