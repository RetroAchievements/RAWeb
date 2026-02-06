<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisplayNameChangeDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $desiredDisplayName,
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
            ->subject('About Your Username Change Request')
            ->markdown('mail.display-name-change.decline', [
                'desiredDisplayName' => $this->desiredDisplayName,
            ]);
    }
}
