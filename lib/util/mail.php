<?php

use Aws\CommandPool;
use Aws\Ses\SesClient;
use RA\ArticleType;
use RA\Permissions;

function mail_ses($to, $subject = '(No subject)', $message = ''): bool
{
    $client = new SesClient([
        'version' => 'latest',
        'region' => getenv('AWS_DEFAULT_REGION'),
        // Note: automatically pulled from env when named properly
        // 'credentials' => [
        //     'key'    => getenv('AWS_ACCESS_KEY_ID'),
        //     'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
        // ],
    ]);

    $recipients = [
        $to,
    ];

    // Queue emails as SendEmail commands
    $i = 100;
    $commands = [];
    foreach ($recipients as $recipient) {
        $commands[] = $client->getCommand('SendEmail', [
            // Pass the message id so it can be updated after it is processed (it's ignored by SES)
            'x-message-id' => $i,
            'Source' => getenv('MAIL_FROM_NAME') . ' <' . getenv('MAIL_FROM_ADDRESS') . '>',
            'Destination' => [
                'ToAddresses' => [$recipient],
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $subject,
                    'Charset' => 'UTF-8',
                ],
                'Body' => [
                    'Html' => [
                        'Data' => $message,
                        'Charset' => 'UTF-8',
                    ],
                ],
            ],
        ]);
        $i++;
    }

    try {
        $pool = new CommandPool($client, $commands, [
            'concurrency' => 10,
            // 'before' => function (CommandInterface $cmd, $iteratorId) {
            //     echo sprintf('About to send %d: %s'.PHP_EOL, $iteratorId, $a['Destination']['ToAddresses'][0]);
            //     error_log('About to send '.$iteratorId.': '.$a['Destination']['ToAddresses'][0]);
            //     $a = $cmd->toArray();
            // },
            // 'fulfilled' => function (ResultInterface $result, $iteratorId) use ($commands) {
            //     echo sprintf(
            //         'Completed %d: %s'.PHP_EOL,
            //         $commands[$iteratorId]['x-message-id'],
            //         $commands[$iteratorId]['Destination']['ToAddresses'][0]
            //     );
            //     error_log('Completed '.$commands[$iteratorId]['x-message-id'].' :'.$commands[$iteratorId]['Destination']['ToAddresses'][0]);
            // },
            // 'rejected' => function (AwsException $reason, $iteratorId) use ($commands) {
            //     echo sprintf(
            //         'Failed %d: %s'.PHP_EOL,
            //         $commands[$iteratorId]['x-message-id'],
            //         $commands[$iteratorId]['Destination']['ToAddresses'][0]
            //     );
            //
            //     error_log('Reason : '.$reason);
            //     error_log('Amazon SES Failed Rejected:'.$commands[$iteratorId]['x-message-id'].' :'.$commands[$iteratorId]['Destination']['ToAddresses'][0]);
            // },
        ]);
        // Initiate the pool transfers
        $promise = $pool->promise();
        // Force the pool to complete synchronously
        $promise->wait();

        return true;
    } catch (Exception $e) {
    }

    return false;
}

function mail_utf8($to, $from_user, $from_email, $subject = '(No subject)', $message = '')
{
    if (empty($to)) {
        return false;
    }

    if (getenv('MAIL_DRIVER') === 'ses') {
        // let amazon ses handle mail sending
        return mail_ses($to, $subject, $message);
    }

    if (getenv('MAIL_DRIVER') === 'mailcatcher') {
        $transport = new Swift_SmtpTransport('mailcatcher', 1025);
        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($subject))
            ->setFrom([$from_email => $from_user])
            ->setTo($to)
            ->setBody($message);

        return $mailer->send($message);
    }

    $from_user = "=?UTF-8?B?" . base64_encode($from_user) . "?=";
    $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers = "From: $from_user <$from_email>\r\n" .
        "Reply-To: $from_user <$from_email>\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers, "-f" . $from_email);
}

function sendValidationEmail($user, $email)
{
    //    This generates and stores (and returns) a new email validation string in the DB.
    $strValidation = generateEmailValidationString($user);
    $strEmailLink = getenv('APP_URL') . "/validateEmail.php?v=$strValidation";

    //$subject = "RetroAchievements.org - Confirm Email: $user";
    $subject = "Welcome to RetroAchievements.org, $user";

    $msg = "You or someone using your email address has attempted to sign up for an account at <a href='" . getenv('APP_URL') . "'>RetroAchievements.org</a><br>" .
        "<br>" .
        "If this was you, please click the following link to confirm this email address and complete sign up:<br>" .
        "<br>" .
        "&nbsp; &nbsp; &nbsp; &nbsp; <a href='$strEmailLink'>$strEmailLink</a><br>" .
        "<br>" .
        "If this wasn't you, please ignore this email.<br>" .
        "<br>" .
        "Thanks! And hope to see you on the forums!<br>" .
        "<br>" .
        "-- Your friends at <a href='" . getenv('APP_URL') . "'>RetroAchievements.org</a><br>";

    // error_log(__FUNCTION__ . " sending mail to $user at address $email");

    $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $subject, $msg);

    // error_log(__FUNCTION__ . " return val: $retVal");

    return $retVal;
}

function sendFriendEmail($user, $email, $type, $friend)
{
    settype($type, 'integer');
    // error_log(__FUNCTION__ . " $user, $email, $type, $friend");

    if ($user == $friend) {
        // error_log(__FUNCTION__ . "not sending mail: what is happening... ( $user == $friend )");
        return false;
    }

    $emailTitle = '';
    $link = '';
    $emailReason = '';

    if ($type == 0) { //    Requesting to be your friend
        $emailTitle = "New Friend Request from $friend";
        $emailReason = "sent you a friend request";
        $link = "<a href='" . getenv('APP_URL') . "/user/$friend'>here</a>";
    } elseif ($type == 1) { //    Friend request confirmed
        $emailTitle = "New Friend confirmed: $friend";
        $emailReason = "confirmed your friend request";
        $link = "<a href='" . getenv('APP_URL') . "/user/$friend'>here</a>";
    } else {
        // error_log(__FUNCTION__ . " bad times...");
        return false; //    must break early! No nonsense emails please!
    }

    $msg = "Hello $user!<br>" .
        "$friend on RetroAchievements has $emailReason!<br>" .
        "Click $link to visit their user page!<br>" .
        "<br>" .
        "Thanks! And hope to see you on the forums!<br>" .
        "<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    if (isAtHome()) {
        error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
        error_log($email);
        error_log($emailTitle);
        error_log($msg);
        $retVal = true;
    } else {
        // error_log(__FUNCTION__ . " sending friend mail to $user at address $email");
        $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
        // error_log(__FUNCTION__ . " return val: $retVal");
    }

    return $retVal;
}

function sendActivityEmail(
    $user,
    $email,
    $actID,
    $activityCommenter,
    $articleType,
    $articleTitle,
    $threadInvolved = null,
    $altURLTarget = null
) {
    if ($user == $activityCommenter || getUserPermissions($user) < Permissions::Unregistered) {
        return false;
    }

    $emailTitle = '';
    $link = '';
    $activityDescription = '';

    switch ($articleType) {
        case ArticleType::Game:
            $emailTitle = "New Game Wall Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/game/$actID'>here</a>";
            $activityDescription = "the game wall for $articleTitle";
            break;

        case ArticleType::Achievement:
            $emailTitle = "New Achievement Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/achievement/$actID'>here</a>";
            $activityDescription = "the achievement wall for $articleTitle";
            break;

        case ArticleType::User:
            $emailTitle = "New User Wall Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/user/$altURLTarget'>here</a>";
            $activityDescription = "your user wall";
            if ($articleTitle != $user) {
                $activityDescription = "$articleTitle's user wall";
            }
            break;

        case ArticleType::Leaderboard:
            $emailTitle = "New Leaderboard Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/leaderboardinfo.php?i=$actID'>here</a>";
            $activityDescription = "the leaderboard wall for $articleTitle";
            break;

        case ArticleType::Forum:
            $emailTitle = "New Forum Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/$altURLTarget'>here</a>";
            $activityDescription = "the forum post \"$articleTitle\"";
            break;

        case ArticleType::AchievementTicket:
            $emailTitle = "New Ticket Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/ticketmanager.php?i=$actID'>here</a>";
            $activityDescription = "the ticket you reported for $articleTitle";
            if (isset($threadInvolved)) {
                $activityDescription = "a ticket for $articleTitle";
            }
            break;

        default:
            // generic messages
            $emailTitle = "New Activity Comment from $activityCommenter";
            $link = "<a href='" . getenv('APP_URL') . "/feed.php?a=$actID'>here</a>";
            $activityDescription = "Your latest activity";
            if (isset($threadInvolved)) {
                $activityDescription = "A thread you've commented in";
            }
            break;
    }

    $msg = "Hello $user!<br>" .
        "$activityCommenter has commented on $activityDescription. " .
        "Click $link to see what they have written!<br>" .
        "<br>" .
        "Thanks! And hope to see you on the forums!<br>" .
        "<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    if (isAtHome()) {
        error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
        error_log($email);
        error_log($emailTitle);
        error_log($msg);
        $retVal = true;
    } else {
        // error_log(__FUNCTION__ . " sending activity mail to $user at address $email");
        $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
        // error_log(__FUNCTION__ . " return val: $retVal");
    }

    return $retVal;
}

function sendRAEmail($to, $header, $body)
{
    $body = stripslashes(nl2br($body));

    if (isAtHome()) {
        error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
        error_log($to);
        error_log($header);
        error_log($body);

        return true;
    }

    return mail_utf8($to, "RetroAchievements.org", "noreply@retroachievements.org", $header, $body);
}

function SendPrivateMessageEmail($user, $email, $title, $contentIn, $fromUser)
{
    if ($user == $fromUser) {
        // error_log(__FUNCTION__ . " not sending mail: I wrote this! ($user == $fromUser)");
        return false;
    }

    $content = stripslashes(nl2br($contentIn));

    //    Also used for Generic text:
    $emailTitle = "New Private Message from $fromUser";
    $link = "<a href='" . getenv('APP_URL') . "/inbox.php'>here</a>";

    $msg = "Hello $user!<br>" .
        "You have received a new private message from $fromUser.<br><br>" .
        "Title: $title<br>" .
        "$content<br><br>" .
        "Click $link to reply!<br>" .
        "Thanks! And hope to see you on the forums!<br>" .
        "<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    if (isAtHome()) {
        error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
        error_log($email);
        error_log($emailTitle);
        error_log($msg);
        $retVal = true;
    } else {
        // error_log(__FUNCTION__ . " sending activity mail to $user at address $email");
        $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
        // error_log(__FUNCTION__ . " return val: $retVal");
    }

    return $retVal;
}

function SendPasswordResetEmail($user, $email, $token)
{
    $emailTitle = "Password Reset Request";
    $link = "<a href='" . getenv('APP_URL') . "/resetPassword.php?u=$user&amp;t=$token'>Confirm Your Email Address</a>";

    $msg = "Hello $user!<br>" .
        "Your account has requested a password reset:<br>" .
        "$link<br>" .
        "Thanks!<br>" .
        "-- Your friends at RetroAchievements.org<br>";

    if (isAtHome()) {
        error_log(__FUNCTION__ . " dumping mail, not sending... no mailserver!");
        error_log("Email: " . $email . ", Title: " . $emailTitle . ", Msg: " . $msg);
        $retVal = true;
    } else {
        // error_log(__FUNCTION__ . " sending activity mail to $user at address $email");
        $retVal = mail_utf8($email, "RetroAchievements.org", "noreply@retroachievements.org", $emailTitle, $msg);
        // error_log(__FUNCTION__ . " return val: $retVal");
    }

    return $retVal;
}
