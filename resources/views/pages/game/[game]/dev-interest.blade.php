<?php

use App\Enums\Permissions;
use App\Models\Game;
use App\Platform\Services\GameDevInterestPageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game', 'can:develop']);
name('game.dev-interest');

render(function (View $view, Game $game, GameDevInterestPageService $pageService) {
    // TODO use a policy -- lift it up to the Folio page middleware. "can:viewDeveloperInterest,game"?
    $user = Auth::user();

    $permissions = (int) $user->getAttribute('Permissions');
    if ($permissions < Permissions::JuniorDeveloper) {
        abort(403);
    }

    if ($permissions < Permissions::Moderator && !hasSetClaimed($user, $game->id, true)) {
        abort(403);
    }
    // END TODO use a policy -- lift it up to the Folio page middleware. "can:viewDeveloperInterest,game"?

    return $view->with($pageService->buildViewData($game));
});

?>

<x-app-layout
    :pageTitle="$pageTitle"
    :pageDescription="$pageDescription"
>
    <div class='navpath'>
        <x-game.breadcrumbs
            :game="$game"
            currentPageLabel="Developer Interest"
        />
    </div>

    <x-game.heading
        :gameId="$game->id"
        :gameTitle="$game->title"
        :consoleId="$game->system->id"
        :consoleName="$game->system->name"
    />

    <x-game.primary-meta
        :imageIcon="media_asset($game->ImageIcon)"
        metaKind="Game"
    />

    <p>The following users have added this game to their Want to Develop list:</p>

    <table>
        @if (count($users) < 1)
            <tr><td>None</td></tr>
        @else
            @foreach ($users as $user)
                <tr><td>{!! userAvatar($user) !!}</td></tr>
            @endforeach
        @endif
    </table>
</x-app-layout>
