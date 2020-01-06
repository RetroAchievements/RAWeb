<?php
/**
 * @return Facebook
 */
function getFacebookConnection()
{
    return new Facebook([
        'appId' => getenv('FACEBOOK_APP_ID'),
        'secret' => getenv('FACEBOOK_SECRET'),
        'appToken' => getenv('FACEBOOK_APP_ID') . '|' . getenv('FACEBOOK_SECRET'),
    ]);
}
