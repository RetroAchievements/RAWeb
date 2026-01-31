<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValidateUserEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $validationToken,
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
            ->subject("Verify your RetroAchievements.org email")
            ->markdown('mail.auth.validate-email', [
                'user' => $notifiable,
                'validationToken' => $this->validationToken,
            ]);
    }
}
