<?php

declare(strict_types=1);

namespace App\Site\Commands;

use App\Platform\Models\Achievement;
use App\Site\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DeleteUsers extends Command
{
    protected $signature = 'ra:site:user:delete';
    protected $description = 'Handle user deletion requests';

    public function handle(): void
    {
        $this->handleTestAccounts();
    }

    private function handleTestAccounts(): void
    {
        $testAccounts = $this->testAccounts()->pluck('id');

        /**
         * Move Scott's test account achievements
         */
        $achievements = Achievement::query()->whereIn('user_id', $testAccounts)->cursor();
        /** @var Achievement $achievement */
        foreach ($achievements as $achievement) {
            $achievement->timestamps = false;
            $achievement->update(['user_id' => 1]);
        }

        /*
         * Delete Test Accounts
         */
        User::whereIn('id', $testAccounts)->delete();
    }

    /**
     * treat as deleted
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    private function testAccounts(): Collection
    {
        return collect([
            ['id' => 2, 'name' => 'Dave'],
            ['id' => 4, 'name' => 'Batman'], // developer
            ['id' => 5, 'name' => 'Steve'],
            ['id' => 6, 'name' => 'Michael'], // developer
            ['id' => 7, 'name' => 'poip'],
            ['id' => 8, 'name' => 'Sonic'], // developer
            ['id' => 9, 'name' => 'qwer'],
            ['id' => 10, 'name' => 'Bob'], // developer
            ['id' => 12, 'name' => 'Jim'],
            ['id' => 13, 'name' => 'qweqwe'],
            ['id' => 14, 'name' => 'qwe'], // developer
            ['id' => 17, 'name' => 'Potato'], // developer
            ['id' => 18, 'name' => 'Alpha'],
            ['id' => 19, 'name' => 'TestUser'],
            ['id' => 20, 'name' => 'TestNewUser'],
            ['id' => 21, 'name' => 'TestScott'],
            ['id' => 22, 'name' => 'FebTest'],
            ['id' => 30, 'name' => 'Toejam'],
            ['id' => 31, 'name' => 'AprilTest'],
            ['id' => 104, 'name' => 'FBTestAccount'],
            ['id' => 390, 'name' => 'AprilTest2'],
            ['id' => 417, 'name' => 'Simon'],
            ['id' => 418, 'name' => 'Simon123'],
            ['id' => 424, 'name' => 'TestAccount'],
            ['id' => 776, 'name' => 'ScottsGreat'],
            ['id' => 957, 'name' => 'Mexico'],
            ['id' => 1096, 'name' => 'ScottJanTest'],
            ['id' => 1545, 'name' => 'AmazonTest'],
            ['id' => 1552, 'name' => 'TheMan'],
            ['id' => 1693, 'name' => 'MrWoman'],
            ['id' => 2001, 'name' => 'MarTest'],
            ['id' => 2061, 'name' => 'DirtySpambot'],
            ['id' => 2104, 'name' => 'TestTest'],
            ['id' => 2359, 'name' => 'TestMay'],
            ['id' => 3017, 'name' => 'PassBreak'],
            ['id' => 10900, 'name' => 'TestRecapAgain'],
            ['id' => 11496, 'name' => 'NewSMPTTest'],
            ['id' => 11498, 'name' => 'TestAgain'],
            ['id' => 11499, 'name' => 'TestAgain2'],
            ['id' => 11538, 'name' => 'ScottTestTest'],
            ['id' => 11785, 'name' => 'WillyMan'],
            ['id' => 11787, 'name' => 'WillyMan2'],
            ['id' => 13489, 'name' => 'ScottTest15'],
            ['id' => 13490, 'name' => 'ScottTest16'],
            ['id' => 13491, 'name' => 'ScottTest17'],
            ['id' => 15563, 'name' => 'Scott2016Test'],
            ['id' => 15564, 'name' => 'Scott2016Test2'],
            ['id' => 29399, 'name' => 'Poop'],
            ['id' => 29400, 'name' => 'Poopsticks'],
            ['id' => 29401, 'name' => 'Poopsticks2'],
            ['id' => 31857, 'name' => 'NoPass'],
            ['id' => 31858, 'name' => 'NoPass2'],
            ['id' => 44077, 'name' => 'qweJune17'],
            ['id' => 50463, 'name' => 'Sept2017Test'],
            // ['id' => 14188, 'name' => 'Server'], // Server User for comments
        ]);
    }
}
