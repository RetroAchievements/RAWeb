<?php

function sanitize_outputs(&...$outputs)
{
    foreach ($outputs as &$output) {
        if (!empty($output)) {
            $output = htmlentities($output, null, null, false);
        }
    }
}

function attributeEscape($input)
{
    // htmlspecialchars escapes a bunch of stuff that the tooltip can't handle
    // (like &rsquo; $frac12; and &deg;). when placed in title or alt fields.
    // just do the bare minimum.
    $input = str_replace("'", "\'", $input);
    $input = str_replace('"', "&quot;", $input);
    return $input;
}

function isValidUsername($userTest)
{
    if (
        empty($userTest)
        || !ctype_alnum($userTest)
        || mb_strlen($userTest) > 20
        || mb_strlen($userTest) < 2
    ) {
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
