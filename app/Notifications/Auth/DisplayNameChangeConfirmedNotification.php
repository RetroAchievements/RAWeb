<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisplayNameChangeConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $newDisplayName,
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
            ->subject('Display Name Change Confirmed')
            ->markdown('mail.display-name-change.confirm', [
                'newDisplayName' => $this->newDisplayName,
            ]);
    }
}
