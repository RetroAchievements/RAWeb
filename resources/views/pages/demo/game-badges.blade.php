<?php

use App\Models\Game;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:root']);

name('demo.game-badges');

render(function (View $view) {
    $gameId = (int) request()->query('game', 0);
    $game = $gameId > 0 ? Game::find($gameId) : null;
    $badges = $game?->badges()->orderByDesc('became_current_at')->get();

    return $view->with(['gameId' => $gameId, 'game' => $game, 'badges' => $badges]);
});

?>

<x-app-layout pageTitle="Game Badges Demo">
    <div class="flex flex-col gap-6">
        <form method="GET" action="" class="flex items-center gap-2">
            <label for="game" class="font-semibold">Game ID:</label>
            <input type="number" id="game" name="game" value="{{ $gameId ?: '' }}" class="px-2 py-1 border" min="1" />
            <button type="submit" class="px-3 py-1 btn">Show badges</button>
        </form>

        @if ($gameId > 0 && !$game)
            <p>No game found with id {{ $gameId }}.</p>
        @elseif ($game)
            <h2 class="text-xl font-semibold">{{ $game->title }} (id {{ $game->id }})</h2>
            <p>Canonical: <code>{{ $game->image_icon_asset_path }}</code></p>

            @if ($badges->isEmpty())
                <p>No game_badges rows for this game.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="py-1">image</th>
                            <th>id</th>
                            <th>path</th>
                            <th>sha1</th>
                            <th>source</th>
                            <th>became current</th>
                            <th>replaced</th>
                            <th>uploader</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($badges as $badge)
                            <tr class="border-t">
                                <td class="py-1"><img src="{{ media_asset($badge->image_asset_path) }}" alt="" width="48" height="48" /></td>
                                <td>{{ $badge->id }}</td>
                                <td><code>{{ $badge->image_asset_path }}</code></td>
                                <td><code>{{ substr($badge->sha1, 0, 8) }}…</code></td>
                                <td>{{ $badge->attribution_source->value }}</td>
                                <td>{{ $badge->became_current_at }}</td>
                                <td>{{ $badge->replaced_at ?? '(current)' }}</td>
                                <td>{{ $badge->uploaded_by_user_id ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </div>
</x-app-layout>
