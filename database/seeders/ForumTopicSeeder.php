<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ForumTopicSeeder extends Seeder
{
    public function run(): void
    {
        if (ForumTopic::count() > 0) {
            return;
        }

        $now = Carbon::now();
        $faker = \Faker\Factory::create();
        $numTopics = 0;
        $numComments = 0;

        // first topic will be between 1 and 6 years old
        $then = $now->clone()->subMinutes(random_int(24 * 60 * 365, 24 * 60 * 365 * 6));

        for ($i = random_int(100, 400); $i >= 0; $i--) {
            // roughly 25% chance to create a new topic
            if (random_int(0, $numTopics * 4) <= $numTopics) {
                $forum = Forum::inRandomOrder()->first();

                $forumTopic = ForumTopic::factory()->create([
                    'Title' => ucfirst($faker->words(random_int(2, 10), true)),
                    'ForumID' => $forum->ID,
                    'DateCreated' => $then,
                    'Updated' => $then,
                ]);
                $forumTopic->save();

                $forumTopicComment = ForumTopicComment::factory()->create([
                    'ForumTopicID' => $forumTopic->ID,
                    'author_id' => $forumTopic->author_id,
                    'Payload' => ForumTopicComment::factory()->randomBody(),
                    'DateCreated' => $then,
                    'DateModified' => $then,
                ]);
                $forumTopicComment->save();

                $forumTopic->LatestCommentID = $forumTopicComment->ID;
                $forumTopic->save();

                $numTopics++;
            }
            // roughly 20% chance to edit an existing comment (slightly less due to integer rounding)
            elseif (random_int(0, $numComments) > $numComments * 0.8) {
                $forumTopicComment = ForumTopicComment::inRandomOrder()->first();
                $forumTopicComment->Payload = ForumTopicComment::factory()->randomBody();
                $forumTopicComment->DateModified = $then;
                $forumTopicComment->save();
            }
            // otherwise, add a comment to an existing topic
            else {
                $forumTopic = ForumTopic::inRandomOrder()->first();

                $forumTopicComment = ForumTopicComment::factory()->create([
                    'ForumTopicID' => $forumTopic->ID,
                    'Payload' => ForumTopicComment::factory()->randomBody(),
                    'DateCreated' => $then,
                    'DateModified' => $then,
                ]);
                $forumTopicComment->save();

                $forumTopic->LatestCommentID = $forumTopicComment->ID;
                $forumTopic->save();
            }

            // update the time to the oldest of four random timestamps between the last
            // activity and now. adjust the upper bound as we go so we don't advance too quickly.
            $upperThen = $lowerThen = $now;
            for ($j = 0; $j < 8; $j++) {
                $randomThen = $faker->dateTimeBetween($then, $upperThen);
                if ($randomThen < $lowerThen) {
                    $lowerThen = $randomThen;
                } else {
                    $upperThen = $randomThen;
                }
            }

            $then = $lowerThen;
        }
    }
}
