<?php

declare(strict_types=1);

namespace App\Notifications\Achievement;

use App\Models\Game;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetRevisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Game $game,
        private bool $isHardcore,
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
            ->subject("{$this->game->title} - Achievement Set Updated")
            ->markdown('mail.notification.set-revision', [
                'user' => $notifiable,
                'game' => $this->game,
                'isHardcore' => $this->isHardcore,
            ]);
    }
}
