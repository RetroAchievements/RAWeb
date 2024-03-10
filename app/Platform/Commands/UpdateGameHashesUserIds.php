<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameHash;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateGameHashesUserIds extends Command
{
    protected $signature = 'ra:platform:game-hash:update-user-ids
                            {gameHashId?}';
    protected $description = 'Update user IDs in the game_hashes table. This step is a precursor to dropping the User column from the table.';

    public function __construct(
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameHashId = $this->argument('gameHashId');

        if ($gameHashId !== null) {
            $gameHash = GameHash::findOrFail($gameHashId);

            $this->info('Updating user ID for game hash [' . $gameHash->id . ']');

            $foundUser = User::where('User', $gameHash->User)->first();
            if ($foundUser) {
                $gameHash->user_id = $foundUser->id;
                $gameHash->save();
            }
        } else {
            $this->info('Updating user IDs for all game hashes.');

            $gameHashes = GameHash::whereNotNull('User')->get();

            $totalCount = $gameHashes->count();
            $progressBar = $this->output->createProgressBar($totalCount);
            $progressBar->start();

            foreach ($gameHashes as $gameHash) {
                $foundUser = User::where('User', $gameHash->User)->first();
                if ($foundUser) {
                    $gameHash->user_id = $foundUser->id;
                    $gameHash->save();

                    $progressBar->advance();
                }
            }

            $progressBar->finish();
        }
    }
}
