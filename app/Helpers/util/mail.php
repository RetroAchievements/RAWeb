<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Mail\CommunityActivityMail;
use App\Mail\ValidateUserEmailMail;
use App\Models\Comment;
use App\Models\User;
use Aws\CommandPool;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

function sendRAEmail(string $to, string $subject, string $body): bool
{
    return mail_utf8($to, $subject, stripslashes(nl2br($body)));
}

function mail_utf8(string $to, string $subject = '(No subject)', string $message = ''): bool
{
    if (empty($to)) {
        return false;
    }

    if (config('mail.default') === 'smtp') {
        return mail_smtp($to, $subject, $message);
    }

    if (config('mail.default') === 'ses') {
        return mail_ses($to, $subject, $message);
    }

    return mail_log($to, $subject, $message);
}

function mail_log(string $to, string $subject = '(No subject)', string $message = ''): bool
{
    $mailParams = ['to' => $to, 'subject' => $subject, 'message' => $message];
    Log::debug('Mail', $mailParams);

    if (app()->environment('testing')) {
        $arr = Cache::store('array')->get('test:emails');
        if ($arr !== null) {
            $arr[] = $mailParams;
            Cache::store('array')->put('test:emails', $arr);
        }
    }

    return true;
}

function mail_smtp(string $to, string $subject = '(No subject)', string $message = ''): bool
{
    /** @var Mailer $mailer */
    $mailer = app()->make(MailerContract::class);

    /** @var SesTransport $transport */
    $transport = $mailer->getSymfonyTransport();

    $email = (new Email())
        ->from(config('mail.from.name') . ' <' . config('mail.from.address') . '>')
        ->to($to)
        ->subject($subject)
        ->html($message);

    $transport->send($email);

    return true;
}

function mail_ses(string $to, string $subject = '(No subject)', string $message = ''): bool
{
    /** @var Mailer $mailer */
    $mailer = app()->make(MailerContract::class);

    /** @var SesTransport $transport */
    $transport = $mailer->getSymfonyTransport();

    $client = $transport->ses();

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
            'Source' => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
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
        Log::error($e->getMessage());

        return false;
    }
}

function sendValidationEmail(User $user, string $email): bool
{
    // This generates and stores (and returns) a new email validation string in the DB.
    $strValidation = generateEmailVerificationToken($user);

    Mail::to($email)->queue(new ValidateUserEmailMail($user, $strValidation));

    return true;
}

function informAllSubscribersAboutActivity(
    int $articleType,
    int $articleID,
    string $activityAuthor,
    int $commentID,
    ?string $onBehalfOfUser = null
): void {
    $subscribers = [];
    $subjectAuthor = null;
    $urlTarget = null;
    $articleTitle = '';

    switch ($articleType) {
        case ArticleType::Game:
            $gameData = getGameData($articleID);
            $subscribers = getSubscribersOfGameWall($articleID);
            $articleTitle = $gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')';
            $urlTarget = "game/$articleID";
            break;

        case ArticleType::Achievement:
            $achievementData = GetAchievementData($articleID);
            $subscribers = getSubscribersOfAchievement($articleID, $achievementData['GameID'], $achievementData['Author']);
            $subjectAuthor = $achievementData['Author'];
            $articleTitle = $achievementData['Title'] . ' (' . $achievementData['GameTitle'] . ')';
            $urlTarget = "achievement/$articleID";
            break;

        case ArticleType::User:  // User wall
            $wallUserData = User::find($articleID);
            $subscribers = getSubscribersOfUserWall($articleID, $wallUserData['User']);
            $subjectAuthor = $wallUserData->display_name;
            $articleTitle = $wallUserData->display_name;
            $urlTarget = "user/" . $wallUserData->display_name;
            break;

        case ArticleType::News:  // News
            break;

        case ArticleType::Leaderboard:  // Leaderboard
            // cannot currently subscribe to leaderboard
            $urlTarget = "leaderboardinfo.php?i=$articleID";
            break;

        case ArticleType::AchievementTicket:  // Ticket
            $ticketData = getTicket($articleID);
            $subscribers = getSubscribersOfTicket($articleID, $ticketData['ReportedBy'], $ticketData['GameID']);
            $subjectAuthor = $ticketData['ReportedBy'];
            $articleTitle = $ticketData['AchievementTitle'] . ' (' . $ticketData['GameTitle'] . ')';
            $urlTarget = route('ticket.show', ['ticket' => $articleID]);
            break;

        default:
            break;
    }

    // some comments are generated by the user "Server" on behalf of other users whom we don't want to notify
    if ($onBehalfOfUser !== null) {
        $activityAuthor = $onBehalfOfUser;
    }

    $payload = null;
    if ($commentID > 0) {
        $urlTarget .= "#comment_$commentID";

        $comment = Comment::find($commentID);
        if ($comment) {
            // this is similar to the logic for being able to create a ticket. the user account
            // must be at least 24 hours old and have at least five minutes on playtime. if not,
            // we don't trust the user and shouldn't broadcast their message to all subscribers
            // as mass emails with junk content could get us flagged for spamming.
            if ($comment->user->created_at->diffInDays() >= 1
                && ($comment->user->playerSessions()->where('duration', '>', 5)->exists()
                    || $comment->user->playerGames()->where('time_taken', '>', 5)->exists())) {
                $payload = nl2br($comment->Payload);
            }
        }
    }

    foreach ($subscribers as $subscriber) {
        $isThirdParty = ($subscriber['User'] != $activityAuthor && ($subjectAuthor === null || $subscriber['User'] != $subjectAuthor));

        if (isset($subscriber['EmailAddress'])) {
            sendActivityEmail(
                isset($subscriber['display_name']) ? $subscriber['display_name'] : $subscriber['User'],
                $subscriber['EmailAddress'],
                $articleID,
                $activityAuthor,
                $articleType,
                $articleTitle,
                $urlTarget,
                $isThirdParty,
                $payload,
            );
        }
    }
}

function sendActivityEmail(
    string $user,
    string $email,
    int $actID,
    string $activityCommenter,
    int $articleType,
    string $articleTitle,
    string $urlTarget,
    bool $threadInvolved = false,
    ?string $payload = null,
): bool {
    if (
        $user === $activityCommenter
        || getUserPermissions($user) < Permissions::Unregistered
        || empty(trim($email))
    ) {
        return false;
    }

    Mail::to($email)->queue(new CommunityActivityMail(
        $user,
        $actID,
        $activityCommenter,
        $articleType,
        $articleTitle,
        $urlTarget,
        $threadInvolved,
        $payload,
    ));

    return true;
}
