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
                    'title' => ucfirst($faker->words(random_int(2, 10), true)),
                    'forum_id' => $forum->id,
                    'created_at' => $then,
                    'updated_at' => $then,
                ]);
                $forumTopic->save();

                $forumTopicComment = ForumTopicComment::factory()->create([
                    'forum_topic_id' => $forumTopic->id,
                    'author_id' => $forumTopic->author_id,
                    'body' => ForumTopicComment::factory()->randomBody(),
                    'created_at' => $then,
                    'updated_at' => $then,
                ]);
                $forumTopicComment->save();

                $forumTopic->latest_comment_id = $forumTopicComment->ID;
                $forumTopic->save();

                $numTopics++;
            }
            // roughly 20% chance to edit an existing comment (slightly less due to integer rounding)
            elseif (random_int(0, $numComments) > $numComments * 0.8) {
                $forumTopicComment = ForumTopicComment::inRandomOrder()->first();
                $forumTopicComment->body = ForumTopicComment::factory()->randomBody();
                $forumTopicComment->updated_at = $then;
                $forumTopicComment->save();
            }
            // otherwise, add a comment to an existing topic
            else {
                $forumTopic = ForumTopic::inRandomOrder()->first();

                $forumTopicComment = ForumTopicComment::factory()->create([
                    'forum_topic_id' => $forumTopic->id,
                    'body' => ForumTopicComment::factory()->randomBody(),
                    'created_at' => $then,
                    'updated_at' => $then,
                ]);
                $forumTopicComment->save();

                $forumTopic->latest_comment_id = $forumTopicComment->ID;
                $forumTopic->save();

                $numComments++;
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
