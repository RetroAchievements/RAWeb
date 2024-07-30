@props([
    'highestPointEarners' => [], // Collection<PlayerGame>
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
                    <th class="text-right">Points</th>
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
                    <x-game.top-achievers.score-row :rank="$rank" :user="$user" :score="$playerGame['points_hardcore']" />
                    @php
                        $rank++;
                    @endphp
                @endforeach
            </tbody>
        </table>
    </div>
</div>
