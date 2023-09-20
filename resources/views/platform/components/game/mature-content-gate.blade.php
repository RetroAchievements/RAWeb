@props([
    'gameId' => 0,
    'gameTitle' => 'Unknown game',
    'consoleId' => 0,
    'consoleName' => 'Unknown console',
    'userWebsitePrefs' => 0,
])

<?php
use App\Site\Enums\UserPreference;

$matureContentPref = UserPreference::Site_SuppressMatureContentWarning;
$newPreferencesValue = ($userWebsitePrefs ?? 0) | (1 << $matureContentPref);
?>

<script>
function matureContentNoticeComponent() {
    return {
        async disableMatureContentWarningPreference() {
            await fetcher('/request/user/update-preferences.php', {
                method: 'POST',
                body: "preferences={{ $newPreferencesValue }}"
            });

            window.location.reload();
        }
    };
}
</script>

<div class="navpath">
    {!!
        renderGameBreadcrumb([
            'GameID' => $gameId,
            'GameTitle' => $gameTitle,
            'ConsoleID' => $consoleId,
            'ConsoleName' => $consoleName
        ], addLinkToLastCrumb: false)
    !!}
</div>

<x-game.heading
    :gameId="$gameId"
    :gameTitle="$gameTitle"
    :consoleId="$consoleId"
    :consoleName="$consoleName"
/>

<p class="text-h4 uppercase mb-6">Warning: This page may contain content not appropriate for all ages.</p>

<div id="confirmation" x-data="matureContentNoticeComponent()">
    <p class="mb-4">Are you sure you want to view this page?</p>

    <div class="flex flex-col sm:flex-row gap-4 sm:gap-2">
        <a href="{{ url('gameList.php?c=' . $consoleId) }}" class="btn leading-normal max-w-fit">No. Get me out of here.</a>
        <a rel="nofollow" href="{{ route('game.show', [$gameId, 'v' => 1]) }}" class="btn leading-normal max-w-fit">Yes. I'm an adult.</a>

        @if ($userWebsitePrefs)
            <button
                class="btn break-words whitespace-normal leading-normal"
                @click="disableMatureContentWarningPreference()"
            >
                Yes. And never ask me again for pages with mature content.
            </button>
        @endif
    </div>
</div>
