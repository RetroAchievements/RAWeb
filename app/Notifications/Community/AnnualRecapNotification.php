<?php

declare(strict_types=1);

namespace App\Notifications\Community;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnualRecapNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $recapData
     */
    public function __construct(
        private array $recapData,
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
            ->subject("RetroAchievements {$this->recapData['year']} Year in Review for {$notifiable->display_name}")
            ->markdown('mail.community.annual-recap', [
                'user' => $notifiable,
                'recapData' => $this->recapData,
            ]);
    }
}
