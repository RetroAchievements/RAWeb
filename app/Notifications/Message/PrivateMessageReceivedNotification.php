<?php

declare(strict_types=1);

namespace App\Notifications\Message;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrivateMessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private User $userFrom,
        private MessageThread $messageThread,
        private Message $message,
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
            UserPreference::EmailOn_PrivateMessage
        );

        return (new MailMessage())
            ->subject("New Private Message from {$this->userFrom->display_name}")
            ->markdown('mail.community.private-message', [
                'userTo' => $notifiable,
                'userFrom' => $this->userFrom,
                'messageThread' => $this->messageThread,
                'message' => $this->message,
                'categoryUrl' => $categoryUrl,
                'categoryText' => 'Unsubscribe from private message emails',
            ])
            ->withSymfonyMessage(function ($message) use ($categoryUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$categoryUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
