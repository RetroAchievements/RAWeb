<x-app-layout
    pageTitle="{{ $gameTitle }} - Developer Interest"
    pageDescription="Developers interested in working on {{ $gameTitle }}"
>
    <div class='navpath'>
        {!! renderGameBreadcrumb(['GameID' => $gameId, 'GameTitle' => $gameTitle, 'ConsoleID' => $consoleId, 'ConsoleName' => $consoleName], true) !!}
        &raquo; <b>Developer Interest</b>
    </div>

    <x-game.heading
        :gameId="$gameId"
        :gameTitle="$gameTitle"
        :consoleId="$consoleId"
        :consoleName="$consoleName"
        :iconUrl="$iconUrl"
    />

    <x-game.primary-meta
        :imageIcon="$imageIcon"
        :metaKind="$metaKind"
    />

    <p>The following users have added this game to their Want to Develop list:</p>

    @if (count($users) < 1)
        <p>None</p>
    @else
        <table>
        @foreach ($users as $user)
            <tr><td>
            {!! userAvatar($user) !!}
            </td></tr>
        @endforeach
        </table>
    @endif
</x-app-layout>
