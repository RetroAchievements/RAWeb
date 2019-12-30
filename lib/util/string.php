<?php
function isValidUsername($userTest)
{
    if (ctype_alnum($userTest) == false) {
        //error_log( "requestcreateuser.php failed 1 - $user $pass $email $email2 " );
        //echo "Username ($user) must consist only of letters or numbers. Please retry.<br>";
        return false;
    }

    if (mb_strlen($userTest) > 20) {
        //error_log( "requestcreateuser.php failed 2 - $user $pass $email $email2 " );
        //echo "Username can be a maximum of 20 characters. Please retry.<br>";
        //log_sql_fail();
        return false;
    }

    if (mb_strlen($userTest) < 2) {
        return false;
    }

    return true;
}

function rand_string($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = mb_strlen($chars);
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, $size - 1)];
    }

    return $str;
}

function multiexplode($delimiters, $string)
{
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return $launch;
}
