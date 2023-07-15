<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Community\Models\Forum;
use App\Community\Models\ForumCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ForumTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Forum::count() > 0) {
            return;
        }

        $forumData = new Collection([
            [
                'ID' => 1,
                'Name' => 'Community',
                'Description' => 'Anything goes in here! How are you today?',
                'DisplayOrder' => 1,
                'forums' => new Collection([
                    [
                        'ID' => 24,
                        'Title' => 'Chat',
                        'Description' => 'Anything goes here!',
                    ],
                    [
                        'ID' => 4,
                        'Title' => 'Introductions',
                        'Description' => 'Introduce yourself! Let everyone know who you are and what you\'re about!',
                    ],
                    [
                        'ID' => 11,
                        'Title' => 'Latest Updates',
                        'Description' => 'The latest and greatest updates to the website will be announced here.',
                    ],
                    [
                        'ID' => 7,
                        'Title' => 'Community Discussion',
                        'Description' => 'What would you like to see on RA? What inspires you as a gamer? Why were games so much better back in the day? :)',
                    ],
                    [
                        'ID' => 20,
                        'Title' => 'Collector\'s Corner',
                        'Description' => 'For fond memories, all kinds of retro games, and bargain bin finds along with buying, selling and trading.',
                    ],
                    [
                        'ID' => 21,
                        'Title' => 'RA em Português!',
                        'Description' => 'Onde todos os nossos amigos lusófonos podem conversar e se divertir!',
                    ],
                    [
                        'ID' => 25,
                        'Title' => 'Competitions',
                        'Description' => 'Site Competitions, wagers, bets, everything! Compete in here!!',
                    ],
                    [
                        'ID' => 26,
                        'Title' => 'Les joueurs francais',
                        'Description' => 'Pour tous nos amis francophones - ce est pour vous!',
                    ],
                ]),
            ],

            [
                'ID' => 2,
                'Name' => 'Mega Drive / Genesis (RAGens)',
                'Description' => 'Discussion about Mega Drive/Genesis games and the RAGens app itself.',
                'DisplayOrder' => 2,
                'forums' => new Collection([
                    [
                        'ID' => 10,
                        'Title' => 'Requests/Ideas',
                        'Description' => 'Have you got an amazing idea for an achievement, and want help making it happen? Share it here!',
                    ],
                    [
                        'ID' => 8,
                        'Title' => 'Bug Reports',
                        'Description' => 'Did something weird happen in RAGens? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                ]),
            ],
            [
                'ID' => 6,
                'Name' => 'SNES (RASnes9x)',
                'Description' => 'What was your favourite SNES game? Post here about SNES games and about the RASnes9x app.',
                'DisplayOrder' => 3,
                'forums' => new Collection([
                    [
                        'ID' => 13,
                        'Title' => 'Requests/Ideas',
                        'Description' => 'What would you like to see achievable on SNES? Star Fox achievements? Mario? Zelda?',
                    ],
                    [
                        'ID' => 14,
                        'Title' => 'Bug Reports',
                        'Description' => 'Did something weird happen in RASnes9x? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                ]),
            ],
            [
                'ID' => 9,
                'Name' => 'NES',
                'Description' => 'For the hardest challenges, and the best victories. This is where it all began',
                'DisplayOrder' => 4,
                'forums' => new Collection([
                    [
                        'ID' => 19,
                        'Title' => 'Bug Reports',
                        'Description' => 'Sometimes things go wrong - letting us know help us fix things, and makes RA even better!',
                    ],
                    [
                        'ID' => 18,
                        'Title' => 'Requests/Ideas',
                        'Description' => 'Any particular NES game you think deserves some achievements? Anything else you\'d like to see? Let us know!',
                    ],
                ]),
            ],
            [
                'ID' => 8,
                'Name' => 'GB/GBA (RAVBA)',
                'Description' => 'The Golden Era of Handheld Gaming - whether it\'s Tetris, Mario or Pokemon, discuss your favourite GB/GBA titles here!',
                'DisplayOrder' => 5,
                'forums' => new Collection([
                    [
                        'ID' => 17,
                        'Title' => 'Bug Reports',
                        'Description' => 'Did something weird happen in RAVBA? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                    [
                        'ID' => 16,
                        'Title' => 'Requests/Ideas',
                        'Description' => 'What achievements work well with Gameboy or GBA titles? What would you like to see? Let us know!',
                    ],
                ]),
            ],
            [
                'ID' => 10,
                'Name' => 'PC Engine',
                'Description' => 'A niche gem of a console, missed by most. The TurboGrafx16/PC Engine brought some unique arcade titles to the home.',
                'DisplayOrder' => 6,
                'forums' => new Collection([
                    [
                        'ID' => 22,
                        'Title' => 'Requests/Ideas',
                        'Description' => 'Any ideas for PC Engine achievements? Let us know and we\'ll make it happen!',
                    ],
                    [
                        'ID' => 23,
                        'Title' => 'Bug Reports',
                        'Description' => 'Have some of our PC Engine achievements gone wrong? Please let us know!',
                    ],
                ]),
            ],

            [
                'ID' => 7,
                'Name' => 'Developers',
                'Description' => 'For general discussion about developing achievements.',
                'DisplayOrder' => 7,
                'forums' => new Collection([
                    [
                        'ID' => 15,
                        'Title' => 'Development Help',
                        'Description' => 'Stuck on something? Need more info about the toolset? Post here!',
                    ],
                    [
                        'ID' => 5,
                        'Title' => 'Tutorials',
                        'Description' => 'Need to understand something more about RAGens or RASnes9x? Creating or improving achievements? What RAM is there to use? What challenges can I create?',
                    ],
                ]),
            ],
        ]);

        $forumData->each(function ($forumCategoryData) {
            $forumCategory = ForumCategory::create(Arr::except($forumCategoryData, ['forums']));
            $forumCategoryData['forums']->each(function ($forum, $index) use ($forumCategory) {
                $forum = Forum::create($forum);
                $forum->CategoryID = $forumCategory->ID;
                $forum->DisplayOrder = $index + 1;
                $forum->save();
            });
        });
    }
}
