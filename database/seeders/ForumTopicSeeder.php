<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ForumTopicSeeder extends Seeder
{
    public function run(): void
    {
        if (ForumTopic::count() > 0) {
            return;
        }

        Forum::all()->each(function (Forum $forum) {
            $forum->topics()->saveMany(ForumTopic::factory()->count(random_int(0, 10))->create([
                'ForumID' => $forum->ID,
            ]));
        });

        ForumTopic::all()->each(function (ForumTopic $forumTopic) {
            /** @var Collection<int, ForumTopicComment> $forumTopicComments */
            $forumTopicComments = $forumTopic->comments()->saveMany(ForumTopicComment::factory()->count(random_int(1, 10))->create([
                'ForumTopicID' => $forumTopic->ID,
            ]));

            $firstComment = $forumTopicComments->first();
            $firstComment->author_id = $forumTopic->author_id;
            $firstComment->save();

            $forumTopic->LatestCommentID = $forumTopicComments->last()?->ID;
            $forumTopic->save();
        });
    }
}
