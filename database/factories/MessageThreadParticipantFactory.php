<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MessageThreadParticipant>
 */
class MessageThreadParticipantFactory extends Factory
{
    protected $model = MessageThreadParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => MessageThread::factory(),
            'user_id' => User::factory(),
            'num_unread' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
