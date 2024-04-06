<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Webhook\WebhookChannel;
use NotificationChannels\Webhook\WebhookMessage;

class UserRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private User $user)
    {
    }

    public function via(): array
    {
        return [WebhookChannel::class];
    }

    public function toWebhook(): WebhookMessage
    {
        return $this->toDiscordWebhook();
    }

    private function toDiscordWebhook(): WebhookMessage
    {
        $user = $this->user;

        $username = $user->User ?? '';
        // TODO $username = $user->display_name ?? '';
        $username = \mb_strlen($username) < 2 ? '???' : $username;
        $username = \mb_strlen($username) > 32 ? \mb_substr($username, 0, 29) . '..' : $username;

        $avatar = asset($user->avatar_url);

        // $username = $this->incident->subject->username;
        // $username = \mb_strlen($username) < 2 ? '???' : $username;
        // $username = \mb_strlen($username) > 32 ? \mb_substr($username, 0, 29) . '..' : $username;
        $color = hexdec('3CB680'); // green = signed up

        $embeds = [];
        $messageElements = [];
        $footerElements = [];
        $fields = [];
        $descriptionElements = [];

        $fieldValue = '[' . $username . '](' . route('user.show', $user) . ')';
        if ($user->country) {
            $fieldValue .= ' :flag_' . \mb_strtolower($user->country) . ':';
        }
        // $fields [] = [
        //     'name' => 'Steam',
        //     'value' => $fieldValue,
        //     'inline' => true,
        // ];

        if ($user->created_at) {
            $descriptionElements[] = 'Joined ' . $user->created_at->format('Y-m-d');
        }

        $embeds[] = [
            'author' => [
                'name' => $username,
                'url' => route('user.show', $user),
                'icon_url' => $avatar,
            ],
            // 'title' => 'Registered',
            // 'url' => route('user.show', $user),
            'description' => \implode(' | ', $descriptionElements),
            // 'description' => mb_substr($message, 0, 2000),
            // 'color'       => $color,
            'thumbnail' => [
                'url' => $avatar,
            ],
            'footer' => [
                'text' => \implode(' | ', $footerElements),
            ],
            'fields' => $fields,
        ];
        $embeds[] = [
            'description' => __('Signed up via site'),
            // 'url' => route('user.show', $user),
            // 'description' => mb_substr($message, 0, 2000),
            'color' => $color,
        ];

        $message = \implode(' | ', $messageElements);

        return WebhookMessage::create()
            ->data(
                [
                    'avatar_url' => $avatar,
                    'username' => $username,
                    'content' => $message,
                    'wait' => true,
                    'embeds' => $embeds,
                    // 'file' => $file,
                    // 'attachments' => $attachments,
                ]
            )
            ->header('Content-Type', 'application/json');
    }

    public function toArray(): array
    {
        return [

        ];
    }
}
