@props([
    'latestMasters' => [],
    'numMasters' => 0,
])

<?php

use App\Models\User;
use Carbon\Carbon;

$rank = $numMasters;

$userIds = collect($latestMasters)->pluck('user_id')->filter()->all();
$users = User::whereIn('ID', $userIds)->get()->keyBy('ID')

?>

<div class="component">
    <h2 class="text-h3">Latest Masters</h2>

    <div class="max-h-[980px] overflow-y-auto">
        <table class='table-highlight'>
            <thead>
                <tr class='do-not-highlight'>
                    <th class="text-right">#</th>
                    <th>User</th>
                    <th>Mastered</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($latestMasters as $mastery)
                    @php
                        $userId = $mastery['user_id'];
                        $masteryUser = $users[$userId] ?? null;
                        if (!$masteryUser) {
                            continue;
                        }

                        $masteryDate = Carbon::createFromTimestampUTC($mastery['last_unlock_hardcore_at']);
                    @endphp

                    <x-game.top-achievers.mastery-row :rank="$rank" :masteryUser="$masteryUser" :masteryDate="$masteryDate" includeTime="false" />

                    @php
                        $rank--;
                    @endphp
                @endforeach
            </tbody>
        </table>
    </div>
</div>
