<?php

declare(strict_types=1);

namespace App\Notifications\Community;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunityFriendNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $fromUser,
    ) {
    }

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $unsubscribeService = app(UnsubscribeService::class);
        $categoryUrl = $unsubscribeService->generateCategoryUrl(
            $notifiable,
            UserPreference::EmailOn_Followed
        );

        return (new MailMessage())
            ->subject("{$this->fromUser->display_name} is now following you")
            ->markdown('mail.community.friend', [
                'toUser' => $notifiable,
                'fromUser' => $this->fromUser,
                'categoryUrl' => $categoryUrl,
                'categoryText' => 'Unsubscribe from follower notification emails',
            ])
            ->withSymfonyMessage(function ($message) use ($categoryUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$categoryUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
