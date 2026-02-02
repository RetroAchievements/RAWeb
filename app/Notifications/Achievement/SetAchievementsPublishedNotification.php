<?php

declare(strict_types=1);

namespace App\Notifications\Achievement;

use App\Models\Game;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetAchievementsPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Game $game,
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
            ->subject("New Achievements Released for {$this->game->title}")
            ->markdown('mail.notification.set-achievements-release', [
                'user' => $notifiable,
                'game' => $this->game,
            ]);
    }
}
