<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MessageIcon extends Component
{
    public ?string $class = null;

    public function render(): View
    {
        /** @var User $user */
        $user = request()->user();

        return view('components.message.icon')
            ->with('count', $user->unread_messages_count);
    }
}
