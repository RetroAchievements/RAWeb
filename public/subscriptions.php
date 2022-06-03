<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

/*
if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    if (getAccountDetails($user, $userDetails) == false) {
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$websitePrefs = $userDetails['websitePrefs'];
$emailAddr = $userDetails['EmailAddress'];
$permissions = $userDetails['Permissions'];
$pageTitle = "My Subscriptions";
$cookie = RA_ReadCookie('RA_Cookie');
$errorCode = seekGET('e');

$subscriptions = getSubscriptions($userDetails['ID']);

RenderDocType();
?>
<head>
    <?php RenderSharedHeader($user); ?>
    <?php RenderTitleTag($pageTitle, $user); ?>
    <script type='text/javascript' src="/js/all.js?v=<?php echo VERSION ?>"></script>
</head>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderErrorCodeWarning('left', $errorCode); ?>
        <div class='component'>
            <h3>Subsriptions</h3>
            <?php foreach ($subscriptions as $subscription): ?>
                <?php var_dump($subscription); ?>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</div>
</body>
</html>
*/
