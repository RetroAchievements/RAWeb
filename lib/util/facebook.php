<?php
/**
 * @return Facebook
 */
function getFacebookConnection()
{
    require_once __DIR__ . '/facebook/facebook.php';

    return new Facebook([
        'appId' => getenv('FACEBOOK_APP_ID'),
        'secret' => getenv('FACEBOOK_SECRET'),
        'appToken' => getenv('FACEBOOK_APP_ID') . '|' . getenv('FACEBOOK_SECRET'),
    ]);
}
