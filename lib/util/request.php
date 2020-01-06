<?php
function seekGET($key, $default = null)
{
    if ($_GET !== false && array_key_exists($key, $_GET)) {
        return $_GET[$key];
    } else {
        return $default;
    }
}

function seekPOST($key, $default = null)
{
    if ($_POST !== false && array_key_exists($key, $_POST)) {
        return $_POST[$key];
    } else {
        return $default;
    }
}

function seekPOSTorGET($key, $default = null, $type = null)
{
    if ($_POST !== false && array_key_exists($key, $_POST)) {
        if (isset($type)) {
            settype($_POST[$key], $type);
        }
        return $_POST[$key];
    } else {
        if ($_GET !== false && array_key_exists($key, $_GET)) {
            if (isset($type)) {
                settype($_GET[$key], $type);
            }
            return $_GET[$key];
        } else {
            if (isset($type)) {
                settype($default, $type);
            }
            return $default;
        }
    }
}

function ValidatePOSTChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_POST)) {
            // error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in POST!");
            return false;
        }
    }

    return true;
}

function ValidateGETChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            // error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in GET!");
            return false;
        }
    }

    return true;
}

function ValidatePOSTorGETChars($charsIn)
{
    $numChars = mb_strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            if (!array_key_exists($charsIn[$i], $_POST)) {
                // error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in GET or POST!");
                return false;
            }
        }
    }

    return true;
}

function CurrentPageURL()
{
    //$pageURL = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
    $pageURL = 'https://';
    $pageURL .= $_SERVER['SERVER_PORT'] != '80' ? $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"] : $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    return $pageURL;
}

function ParseCURLGetImage($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_URL, getenv('APP_URL') . "/$url");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function ParseCURLPage($url, $postString = "")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, getenv('APP_URL') . "/$url");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
