<?php

use App\Platform\Enums\AchievementType;

?>
<x-app-layout>
    <h1>Cards</h1>

    <h3>User</h3>
    <div class="flex justify-between">
        <div class="mb-3">
            <div>Label & Icon</div>
            <div>
                {!! userAvatar('Scott') !!}
            </div>
            <div>
                {!! userAvatar('Jamiras') !!}
            </div>
            <div>
                {!! userAvatar('luchaos') !!}
            </div>
        </div>
        <div class="mb-3">
            <div>Label & Icon</div>
            {!! userAvatar('Scott', label: true, icon: true) !!}
        </div>
        <div class="mb-3">
            <div>Label</div>
            {!! userAvatar('Scott', label: true, icon: false) !!}
        </div>
        <div class="mb-3">
            <div>Icon</div>
            {!! userAvatar('Scott', label: false, icon: true) !!}
        </div>
        <div class="mb-3">
            <div>Label</div>
            {!! userAvatar('Scott', label: true) !!}
        </div>
        <div class="mb-3">
            <div>Icon</div>
            {!! userAvatar('Scott', label: false) !!}
        </div>
        <div class="mb-3">
            <div>Icon</div>
            {!! userAvatar('Scott', icon: true) !!}
        </div>
        <div class="mb-3">
            <div>Label</div>
            {!! userAvatar('Scott', icon: false) !!}
        </div>
        <div>
            {!! renderUserCard('Scott') !!}
        </div>
    </div>

    <h3>Achievement</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                {!! achievementAvatar(1, context: 'Scott') !!}
            </div>
            <div class="mb-3">
                <?= achievementAvatar(
                    [
                        'ID' => 1,
                        'Title' => 'Achievement 1 with pre-rendered tooltip',
                    ],
                ) ?>
            </div>
            <div class="mb-3">
                <?= achievementAvatar(
                    [
                        'ID' => 2,
                        'Title' => 'Achievement 2 with pre-fetched tooltip data',
                        'Description' => 'Description Preloaded Data',
                        'Points' => 'Points Preloaded Data',
                        'BadgeName' => '000000',
                        'DateAwarded' => date("Y-m-d"),
                        'HardcoreAchieved' => date("Y-m-d"),
                        'Type' => AchievementType::Progression,
                    ]
                )
                ?>
            </div>
        </div>
        <div>
            {!! renderAchievementCard(1, context: 'Unlocked') !!}
            <?= renderAchievementCard([
                'ID' => 'ID Preloaded Data',
                'Title' => 'Title Preloaded Data',
                'Description' => 'Description Preloaded Data',
                'Points' => 'Points Preloaded Data',
                'BadgeName' => '000000',
                'Unlock' => 'Unlock Preloaded Data',
            ]) ?>
        </div>
    </div>

    <h3>Game</h3>
    <div class="grid">
        <div>
            <div class="mb-3">
                {!! gameAvatar(1) !!}
            </div>
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="1667" />
            <x-game-card gameId="17078" />
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="2791" />
            <x-game-card gameId="1471" />
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="17953" />
            <x-game-card gameId="1" />
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="12798" />
            <x-game-card gameId="12192" />
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="586" />
            <x-game-card gameId="22561" />
        </div>
        <div class="flex w-full justify-between items-start mb-2">
            <x-game-card gameId="20491" />
            <x-game-card gameId="16498" />
        </div>
    </div>

    <h3>Hub</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                {!! gameAvatar(8935) !!}
            </div>
            <div>
                <x-game-card gameId="8935" />
            </div>
        </div>
    </div>

    <h3>Ticket</h3>
    <div class="flex justify-between">
        <div>
            <div class="mb-3">
                {!! ticketAvatar(1) !!}
            </div>
        </div>
        <div>
            {!! renderTicketCard(1) !!}
        </div>
    </div>
</x-app-layout>
