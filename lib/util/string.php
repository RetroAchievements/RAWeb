<?php

function sanitize_outputs(&...$outputs): void
{
    foreach ($outputs as &$output) {
        if (!empty($output)) {
            $output = htmlentities($output, null, null, false);
        }
    }
}

function attributeEscape(?string $input): string
{
    if (!$input) {
        return '';
    }

    // htmlspecialchars escapes a bunch of stuff that the tooltip can't handle
    // (like &rsquo; $frac12; and &deg;). when placed in title or alt fields.
    // just do the bare minimum.
    $input = str_replace("'", "&#39;", $input);

    return str_replace('"', "&quot;", $input);
}

function isValidUsername($userTest): bool
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

function rand_string($length): string
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $size = mb_strlen($chars);
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, $size - 1)];
    }

    return $str;
}

function separateList(string $items): array
{
    // replace allowed delimiters with spaces
    $items = str_replace([
        ',',
        ';',
        "\t",
        '|',
    ], ' ', $items);

    // split into array, remove empty values, reset keys
    $items = explode(' ', $items);
    $items = array_filter($items);

    return array_values($items);
}

function utf8_sanitize(string $input): string
{
    return mb_convert_encoding($input, "UTF-8", "UTF-8");
}

function implodeInts(string $separator, array $array): string
{
    $result = '';
    foreach ($array as $item) {
        if (is_int($item) || is_numeric($item)) {
            if ($result != '') {
                $result .= ',';
            }
            $result .= $item;
        }
    }
    return $result;
}
