@props([
    'userMassData' => [],
])

<?php
use App\Platform\Models\PlayerSession;
use App\Platform\Models\Game;
use Illuminate\Support\Carbon;

$mostRecentSession = PlayerSession::where('user_id', $userMassData['ID'])
    ->with('game')
    ->orderBy('created_at', 'desc')
    ->first();

// If there's no session for some reason, try to fall back to the user record.
$sessionGame = $mostRecentSession?->game;
if (!$mostRecentSession) {
    $sessionGame = Game::with('system')->find($userMassData['LastGame']['ID']);
}

$mostRecentRichPresenceMessage = (
    $mostRecentSession?->rich_presence
    ?? $userMassData['RichPresenceMsg']
    ?? null
);

$parsedDate = Carbon::parse($mostRecentSession?->rich_presence_updated_at);
?>

@if ($sessionGame)
    <div class="mb-6">
        <div class="flex w-full items-center gap-x-1.5 mb-0.5">
            <p role="heading" aria-level="2" class="text-2xs font-bold">
                Most Recently Played

                @if ($mostRecentSession?->rich_presence_updated_at)
                    <p class="smalldate min-w-auto cursor-help" title="{{ $parsedDate->format('F j Y, g:ia') }}">
                        {{ $parsedDate->diffForHumans() }}
                    </p>
                @endif
            </p>
        </div>

        <div class="w-full p-2 bg-embed flex flex-col gap-y-2 rounded">
            <x-game.multiline-avatar
                :gameId="$sessionGame->ID"
                :gameTitle="$sessionGame->Title"
                :gameImageIcon="$sessionGame->ImageIcon"
                :consoleId="$sessionGame->system->ID"
                :consoleName="$sessionGame->system->Name"
            />

            @if (
                $mostRecentRichPresenceMessage
                && $mostRecentRichPresenceMessage !== 'Unknown'
                && $mostRecentRichPresenceMessage !== 'Playing ' . $sessionGame->Title
            )
                <p class="text-2xs">
                    {{ $mostRecentRichPresenceMessage }}
                </p>
            @endif
        </div>
    </div>
@endif
