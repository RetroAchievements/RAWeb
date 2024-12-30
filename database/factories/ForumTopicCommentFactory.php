<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ForumTopicComment>
 */
class ForumTopicCommentFactory extends Factory
{
    protected $model = ForumTopicComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'body' => ucwords(fake()->words(2, true)),
            'author_id' => $user->ID,
            'authorized_at' => Carbon::now(),
            'is_authorized' => 1,
        ];
    }

    public function randomBody(): string
    {
        $body = fake()->paragraphs(random_int(1, 5), true);

        while (true) {
            $link = null;
            switch (random_int(0, 20)) {
                case 1:
                    $link = "[user=" . User::inRandomOrder()->first()->id . "]";
                    break;

                case 2:
                    $link = "[ach=" . Achievement::inRandomOrder()->first()->id . "]";
                    break;

                case 3:
                    $link = "[game=" . Game::inRandomOrder()->first()->id . "]";
                    break;

                case 9:
                    $link = "[url=https://www.google.com]Google[/url]";
                    break;

                case 12:
                case 13:
                    $link = "[b]" . fake()->words(random_int(1, 5), true) . "[/b]";
                    break;
                case 14:
                case 15:
                    $link = "[i]" . fake()->words(random_int(1, 5), true) . "[/i]";
                    break;
                case 16:
                    $link = "[u]" . fake()->words(random_int(1, 5), true) . "[/u]";
                    break;
            }

            if ($link === null) {
                break;
            }

            $length = strlen($body);
            $startIndex = $endIndex = random_int(0, $length);
            while ($startIndex > 0 && !ctype_space($body[$startIndex - 1])) {
                $startIndex--;
            }

            while ($endIndex < $length && !ctype_space($body[$endIndex])) {
                $endIndex++;
            }

            $body = substr($body, 0, $startIndex) . $link . substr($body, $endIndex);
        }

        return $body;
    }
}
