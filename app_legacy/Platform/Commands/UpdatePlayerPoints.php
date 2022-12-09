<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Commands;

use Illuminate\Console\Command;
use LegacyApp\Site\Models\StaticData;
use LegacyApp\Site\Models\User;

class UpdatePlayerPoints extends Command
{
    protected $signature = 'ra-legacy:platform:player:update-points {username?}';
    protected $description = 'Calculate player points';

    public function handle(): void
    {
        $username = $this->argument('username');
        if (!empty($username)) {
            $this->calculate($username);

            return;
        }

        $staticData = StaticData::first();

        $userId = $staticData['NextUserIDToScan'];
        for ($i = 0; $i < 3; $i++) {
            /** @var ?User $user */
            $user = User::find($userId);
            if ($user) {
                $this->calculate($user->User);
            }
            // get next highest user ID
            $userId = User::where('ID', '>', $userId)
                ->hasAnyPoints()->min('ID') ?? 1;
        }

        StaticData::first()->update([
            'NextUserIDToScan' => $userId,
        ]);
    }

    private function calculate(string $username): void
    {
        recalculatePlayerPoints($username);
        recalculateDeveloperContribution($username);
    }
}
