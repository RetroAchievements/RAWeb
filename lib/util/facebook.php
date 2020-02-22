<?php
/**
 * @return Facebook
 */
function getFacebookConnection()
{
    require_once __DIR__ . '/facebook/facebook.php';

    return new Facebook([
        'appId' => getenv('FACEBOOK_CLIENT_ID'),
        'secret' => getenv('FACEBOOK_CLIENT_SECRET'),
        'appToken' => getenv('FACEBOOK_CLIENT_ID') . '|' . getenv('FACEBOOK_CLIENT_SECRET'),
    ]);
}
