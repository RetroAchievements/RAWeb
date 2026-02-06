<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestAccountDeleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject("{$notifiable->display_name} Requested Account Deletion")
            ->markdown('mail.notification.account-deletion', [
                'user' => $notifiable,
            ]);
    }
}
