<?php

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionNotificationService;
use App\Community\Services\SubscriptionService;
use App\Enums\UserPreference;
use App\Mail\CommunityActivityMail;
use App\Mail\ValidateUserEmailMail;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use Aws\CommandPool;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

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
    CommentableType $commentableType,
    int $commentableId,
    User $activityAuthor,
    int $commentID,
    ?string $onBehalfOfUser = null,
): void {
    $subjectAuthor = null;
    $urlTarget = null;
    $articleTitle = '';
    $articleEmailPreference = UserPreference::EmailOn_ActivityComment;
    $subscriptionSubjectType = null;

    switch ($commentableType) {
        case CommentableType::Game:
            $game = Game::with('system')->find($commentableId);
            if (!$game) {
                return;
            }

            $articleTitle = "{$game->title} ({$game->system->name})";
            $urlTarget = route('game.show', ['game' => $game, 'tab' => 'community']);
            $articleEmailPreference = UserPreference::EmailOn_AchievementComment;
            $subscriptionSubjectType = SubscriptionSubjectType::GameWall;
            break;

        case CommentableType::Achievement:
            $achievement = Achievement::with(['game', 'developer'])->find($commentableId);
            if (!$achievement) {
                return;
            }

            $articleTitle = "{$achievement->title} ({$achievement->game->title})";
            $urlTarget = route('achievement.show', $achievement);
            $subjectAuthor = $achievement->developer;
            $articleEmailPreference = UserPreference::EmailOn_AchievementComment;
            $subscriptionSubjectType = SubscriptionSubjectType::Achievement;
            break;

        case CommentableType::User:  // User wall
            $wallUser = User::find($commentableId);
            if (!$wallUser) {
                return;
            }

            $articleTitle = $wallUser->display_name;
            $urlTarget = route('user.show', $wallUser);
            $subjectAuthor = $wallUser;
            $articleEmailPreference = UserPreference::EmailOn_UserWallComment;
            $subscriptionSubjectType = SubscriptionSubjectType::UserWall;
            break;

        case CommentableType::Leaderboard:  // Leaderboard
            // note: cannot currently explicitly subscribe to leaderboard
            $leaderboard = Leaderboard::with('game')->find($commentableId);
            if (!$leaderboard) {
                return;
            }

            $articleTitle = "{$leaderboard->title} ({$leaderboard->game->title})";
            $urlTarget = "leaderboardinfo.php?i=$commentableId";
            $articleEmailPreference = UserPreference::EmailOn_AchievementComment;
            $subscriptionSubjectType = SubscriptionSubjectType::Leaderboard;
            break;

        case CommentableType::AchievementTicket:  // Ticket
            $ticket = Ticket::with(['achievement.game', 'reporter'])->find($commentableId);
            if (!$ticket) {
                return;
            }

            $articleTitle = "{$ticket->achievement->title} ({$ticket->achievement->game->title})";
            $urlTarget = route('ticket.show', ['ticket' => $ticket->id]);
            $subjectAuthor = $ticket->reporter;
            $articleEmailPreference = UserPreference::EmailOn_TicketActivity;
            $subscriptionSubjectType = SubscriptionSubjectType::AchievementTicket;
            break;

        default:
            break;
    }

    if ($subscriptionSubjectType === null) {
        return;
    }

    // some comments are generated by the user "Server" on behalf of other users whom we don't want to notify
    if ($onBehalfOfUser !== null) {
        $activityAuthor = User::whereName($onBehalfOfUser)->first();
    }

    $payload = null;
    if ($commentID > 0) {
        // For supported comment types, use the intelligent redirect route that
        // handles pagination correctly. For other types (like tickets), append the anchor directly.
        if ($commentableType->supportsCommentRedirect()) {
            $urlTarget = route('comment.show', ['comment' => $commentID]);
        } else {
            $urlTarget .= "#comment_$commentID";
        }

        $comment = Comment::find($commentID);
        if ($comment) {
            // this is similar to the logic for being able to create a ticket. the user account
            // must be at least 24 hours old and have at least five minutes on playtime. if not,
            // we don't trust the user and shouldn't broadcast their message to all subscribers
            // as mass emails with junk content could get us flagged for spamming.
            if ($comment->user->created_at->diffInDays() >= 1
                && ($comment->user->playerSessions()->where('duration', '>', 5)->exists()
                    || $comment->user->playerAchievementSets()->where('time_taken', '>', 5)->exists())) {
                $payload = nl2br($comment->body);
            }
        }
    }

    $subscriptionService = new SubscriptionService();
    $subscribers = $subscriptionService->getSegmentedSubscriberIds($subscriptionSubjectType, $commentableId, $subjectAuthor?->id);

    $notificationService = new SubscriptionNotificationService();
    $notificationService->queueNotifications($subscribers['implicitlySubscribedNotifyLater'],
        $subscriptionSubjectType, $commentableId, $commentID, $articleEmailPreference);

    $emailTargets = $notificationService->getEmailTargets(
        array_merge($subscribers['explicitlySubscribed'], $subscribers['implicitlySubscribedNotifyNow']),
        $articleEmailPreference);

    foreach ($emailTargets as $subscriber) {
        $isThirdParty =
            ($activityAuthor === null || !$activityAuthor->is($subscriber))
            && ($subjectAuthor === null || !$subjectAuthor->is($subscriber));

        sendActivityEmail(
            $subscriber,
            $commentableId,
            $activityAuthor,
            $commentableType,
            $articleTitle,
            $urlTarget,
            $isThirdParty,
            $payload,
        );
    }
}

function sendActivityEmail(
    User $user,
    int $actID,
    ?User $activityCommenter,
    CommentableType $commentableType,
    string $articleTitle,
    string $urlTarget,
    bool $threadInvolved = false,
    ?string $payload = null,
): bool {
    if (
        $user->is($activityCommenter)
        || $user->isGone()
        || $user->isInactive()
        || empty($user->email)
    ) {
        return false;
    }

    Mail::to($user->email)->queue(new CommunityActivityMail(
        $user,
        $actID,
        $activityCommenter?->display_name ?? $activityCommenter?->username,
        $commentableType,
        $articleTitle,
        $urlTarget,
        $threadInvolved,
        $payload,
    ));

    return true;
}
