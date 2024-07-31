@props([
    'highestPointEarners' => [], // Collection<PlayerGame>
    'isEvent' => false,
])

@php

use App\Models\User;

@endphp

<div class="component">
    <h2 class="text-h3">Most Points Earned</h2>

    <div class="max-h-[980px] overflow-y-auto">
        <table class='table-highlight'>
            <thead>
                <tr class='do-not-highlight'>
                    <th class="text-right">#</th>
                    <th>User</th>
                    @if ($isEvent)
                    <th class="text-right">Achievements</th>
                    @else
                    <th class="text-right">Points</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @php
                    $rank = 1;
                @endphp
                @foreach ($highestPointEarners as $playerGame)
                    @php
                        $user = User::find($playerGame['user_id'])
                    @endphp
                    @if ($isEvent)
                        <x-game.top-achievers.score-row :rank="$rank" :user="$user" :score="$playerGame['achievements_unlocked_hardcore']" />
                    @else
                        <x-game.top-achievers.score-row :rank="$rank" :user="$user" :score="$playerGame['points_hardcore']" />
                    @endif
                    @php
                        $rank++;
                    @endphp
                @endforeach
            </tbody>
        </table>
    </div>
</div>
