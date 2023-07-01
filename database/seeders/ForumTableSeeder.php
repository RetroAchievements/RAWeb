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
                'id' => 1,
                'title' => 'Community',
                'description' => 'Anything goes in here! How are you today?',
                'order_column' => 1,
                'forums' => new Collection([
                    [
                        'id' => 24,
                        'title' => 'Chat',
                        'description' => 'Anything goes here!',
                    ],
                    [
                        'id' => 4,
                        'title' => 'Introductions',
                        'description' => 'Introduce yourself! Let everyone know who you are and what you\'re about!',
                    ],
                    [
                        'id' => 11,
                        'title' => 'Latest Updates',
                        'description' => 'The latest and greatest updates to the website will be announced here.',
                    ],
                    [
                        'id' => 7,
                        'title' => 'Community Discussion',
                        'description' => 'What would you like to see on RA? What inspires you as a gamer? Why were games so much better back in the day? :)',
                    ],
                    [
                        'id' => 20,
                        'title' => 'Collector\'s Corner',
                        'description' => 'For fond memories, all kinds of retro games, and bargain bin finds along with buying, selling and trading.',
                    ],
                    [
                        'id' => 21,
                        'title' => 'RA em Português!',
                        'description' => 'Onde todos os nossos amigos lusófonos podem conversar e se divertir!',
                    ],
                    [
                        'id' => 25,
                        'title' => 'Competitions',
                        'description' => 'Site Competitions, wagers, bets, everything! Compete in here!!',
                    ],
                    [
                        'id' => 26,
                        'title' => 'Les joueurs francais',
                        'description' => 'Pour tous nos amis francophones - ce est pour vous!',
                    ],
                ]),
            ],

            [
                'id' => 2,
                'title' => 'Mega Drive / Genesis (RAGens)',
                'description' => 'Discussion about Mega Drive/Genesis games and the RAGens app itself.',
                'order_column' => 2,
                'forums' => new Collection([
                    [
                        'id' => 10,
                        'title' => 'Requests/Ideas',
                        'description' => 'Have you got an amazing idea for an achievement, and want help making it happen? Share it here!',
                    ],
                    [
                        'id' => 8,
                        'title' => 'Bug Reports',
                        'description' => 'Did something weird happen in RAGens? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                ]),
            ],
            [
                'id' => 6,
                'title' => 'SNES (RASnes9x)',
                'description' => 'What was your favourite SNES game? Post here about SNES games and about the RASnes9x app.',
                'order_column' => 3,
                'forums' => new Collection([
                    [
                        'id' => 13,
                        'title' => 'Requests/Ideas',
                        'description' => 'What would you like to see achievable on SNES? Star Fox achievements? Mario? Zelda?',
                    ],
                    [
                        'id' => 14,
                        'title' => 'Bug Reports',
                        'description' => 'Did something weird happen in RASnes9x? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                ]),
            ],
            [
                'id' => 9,
                'title' => 'NES',
                'description' => 'For the hardest challenges, and the best victories. This is where it all began',
                'order_column' => 4,
                'forums' => new Collection([
                    [
                        'id' => 19,
                        'title' => 'Bug Reports',
                        'description' => 'Sometimes things go wrong - letting us know help us fix things, and makes RA even better!',
                    ],
                    [
                        'id' => 18,
                        'title' => 'Requests/Ideas',
                        'description' => 'Any particular NES game you think deserves some achievements? Anything else you\'d like to see? Let us know!',
                    ],
                ]),
            ],
            [
                'id' => 8,
                'title' => 'GB/GBA (RAVBA)',
                'description' => 'The Golden Era of Handheld Gaming - whether it\'s Tetris, Mario or Pokemon, discuss your favourite GB/GBA titles here!',
                'order_column' => 5,
                'forums' => new Collection([
                    [
                        'id' => 17,
                        'title' => 'Bug Reports',
                        'description' => 'Did something weird happen in RAVBA? Crash? Unexpected behaviour? Please let me know with as much detail as possible!',
                    ],
                    [
                        'id' => 16,
                        'title' => 'Requests/Ideas',
                        'description' => 'What achievements work well with Gameboy or GBA titles? What would you like to see? Let us know!',
                    ],
                ]),
            ],
            [
                'id' => 10,
                'title' => 'PC Engine',
                'description' => 'A niche gem of a console, missed by most. The TurboGrafx16/PC Engine brought some unique arcade titles to the home.',
                'order_column' => 6,
                'forums' => new Collection([
                    [
                        'id' => 22,
                        'title' => 'Requests/Ideas',
                        'description' => 'Any ideas for PC Engine achievements? Let us know and we\'ll make it happen!',
                    ],
                    [
                        'id' => 23,
                        'title' => 'Bug Reports',
                        'description' => 'Have some of our PC Engine achievements gone wrong? Please let us know!',
                    ],
                ]),
            ],

            [
                'id' => 7,
                'title' => 'Developers',
                'description' => 'For general discussion about developing achievements.',
                'order_column' => 7,
                'forums' => new Collection([
                    [
                        'id' => 15,
                        'title' => 'Development Help',
                        'description' => 'Stuck on something? Need more info about the toolset? Post here!',
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tutorials',
                        'description' => 'Need to understand something more about RAGens or RASnes9x? Creating or improving achievements? What RAM is there to use? What challenges can I create?',
                    ],
                ]),
            ],
        ]);

        $forumData->each(function ($forumCategoryData) {
            $forumCategory = ForumCategory::create(Arr::except($forumCategoryData, ['forums']));
            $forumCategoryData['forums']->each(function ($forum, $index) use ($forumCategory) {
                $forum = Forum::create($forum);
                $forum->forum_category_id = $forumCategory->id;
                $forum->order_column = $index + 1;
                $forum->save();
            });
        });
    }
}
