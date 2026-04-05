<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\MessageThread;
use App\Models\User;

class MutedUserMessageAlert extends Alert
{
    public function __construct(
        public readonly User $user,
        public readonly MessageThread $messageThread,
    ) {
    }

    /**
     * "Muted user [SomePerson](<https://retroachievements.org/user/SomePerson>) sent [a DM](<https://retroachievements.org/message-thread/123>) to RAdmin"
     */
    public function toDiscordMessage(): string
    {
        $userUrl = route('user.show', ['user' => $this->user]);
        $threadUrl = route('message-thread.show', ['messageThread' => $this->messageThread->id]);

        return sprintf(
            'Muted user [%s](<%s>) sent [a DM](<%s>) to RAdmin',
            $this->user->display_name,
            $userUrl,
            $threadUrl,
        );
    }
}
