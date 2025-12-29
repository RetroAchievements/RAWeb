<?php

use App\Models\Game;
use App\Models\User;
?>

@props([
    'user' => null, // User
])

<?php
/** @var ?User $user */
$sessionGame = $user?->LastGameID
    ? Game::with('system')->find($user->LastGameID)
    : null;

$richPresenceMessage = $user?->RichPresenceMsg;
$richPresenceDate = $user?->RichPresenceMsgDate;
?>

@if ($sessionGame)
    <div class="mb-6">
        <div class="flex w-full items-center gap-x-1.5 mb-0.5">
            <p role="heading" aria-level="2" class="text-2xs font-bold">
                Most Recently Played

                @if ($richPresenceDate)
                    <p class="smalldate min-w-auto cursor-help" title="{{ $richPresenceDate->format('F j Y, g:ia') }}">
                        {{ $richPresenceDate->diffForHumans() }}
                    </p>
                @endif
            </p>
        </div>

        <div class="w-full p-2 bg-embed flex flex-col gap-y-2 rounded">
            <x-game.multiline-avatar
                :gameId="$sessionGame->id"
                :gameTitle="$sessionGame->title"
                :gameImageIcon="$sessionGame->image_icon_asset_path"
                :consoleId="$sessionGame->system->id"
                :consoleName="$sessionGame->system->name"
            />

            @if (
                $richPresenceMessage
                && $richPresenceMessage !== 'Unknown'
                && $richPresenceMessage !== 'Playing ' . $sessionGame->title
            )
                <p class="text-2xs" style="word-break: break-word;">
                    {{ $richPresenceMessage }}
                </p>
            @endif
        </div>
    </div>
@endif
