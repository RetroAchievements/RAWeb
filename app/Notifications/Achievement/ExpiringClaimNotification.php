<?php

declare(strict_types=1);

namespace App\Notifications\Achievement;

use App\Models\AchievementSetClaim;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpiringClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private AchievementSetClaim $claim,
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
        return (new MailMessage())
            ->subject('Claim expiring soon')
            ->markdown('mail.notification.expiring-claim', [
                'claim' => $this->claim,
            ]);
    }
}
