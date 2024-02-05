<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateMessages extends Command
{
    protected $signature = 'ra:platform:messages:migrate-to-threads';
    protected $description = 'Sync messages';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $count = Message::where('thread_id', 0)->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // populate author_id for all records
        DB::statement("UPDATE messages m SET m.author_id = (SELECT u.ID FROM UserAccounts u WHERE u.User = m.UserFrom)");

        // delete records associated to non-existant users
        DB::statement("DELETE FROM messages WHERE author_id=0");

        // process remaining unprocessed records (thread_id=0)
        // have to do this in batches to prevent exhausting memory
        // due to requesting payloads (message content)
        Message::where('thread_id', 0)->chunkById(100, function ($messages) use ($progressBar) {
            foreach ($messages as $message) {
                $this->migrateMessage($message);
                $progressBar->advance();
            }
        });

        $count = Message::where('thread_id', 0)->count();
        if ($count == 0) {
            // all messages sync'd. add the foreign keys so deletes will cascade
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeysFound = $sm->listTableForeignKeys('messages');

            $foundThreadForeignKey = false;
            $foundAuthorForeignKey = false;
            foreach ($foreignKeysFound as $foreignKey) {
                if ($foreignKey->getName() == 'messages_thread_id_foreign') {
                    $foundThreadForeignKey = true;
                } elseif ($foreignKey->getName() == 'messages_author_id_foreign') {
                    $foundAuthorForeignKey = true;
                }
            }

            if (!$foundThreadForeignKey) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->foreign('thread_id')->references('ID')->on('message_threads')->onDelete('cascade');
                });
            }

            if (!$foundAuthorForeignKey) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
                });
            }
        }

        // automatically mark bug report notifications as deleted by the sender if
        // the recipient hasn't replied to them.
        DB::statement("UPDATE message_thread_participants mtp
                       INNER JOIN message_threads mt ON mt.id=mtp.thread_id
                       INNER JOIN messages m ON m.thread_id=mtp.thread_id AND m.author_id=mtp.user_id
                       SET mtp.deleted_at=mtp.updated_at
                       WHERE mt.num_messages=1 AND mt.title LIKE 'Bug Report (%'");

        $progressBar->finish();
        $this->line(PHP_EOL);
    }

    private function migrateMessage(Message $message): void
    {
        // recipient can be entered by user. trim whitespace before trying to match
        $message->UserTo = trim($message->UserTo);
        $userTo = User::withTrashed()->where('User', $message->UserTo)->first();
        if (!$userTo) {
            $message->delete();

            return;
        }

        $thread = null;
        if (strtolower(substr($message->Title, 0, 4)) == 're: ') {
            $threadId = Message::where('title', '=', substr($message->Title, 4))
                ->where('UserFrom', '=', $message->UserTo)
                ->where('UserTo', '=', $message->UserFrom)
                ->where('id', '<', $message->id)
                ->value('thread_id');
            if ($threadId > 0) {
                $thread = MessageThread::firstWhere('id', $threadId);
            }
        }

        if ($thread === null) {
            $thread = new MessageThread([
                'title' => $message->Title,
                'created_at' => $message->created_at,
                'updated_at' => $message->created_at,
            ]);
            $thread->save();

            $participantTo = new MessageThreadParticipant([
                'user_id' => $userTo->ID,
                'thread_id' => $thread->id,
                'created_at' => $message->created_at,
                'updated_at' => $message->created_at,
            ]);
            $participantTo->save();

            if ($message->author_id != $userTo->ID) {
                $participantFrom = new MessageThreadParticipant([
                    'user_id' => $message->author_id,
                    'thread_id' => $thread->id,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->created_at,
                ]);
                $participantFrom->save();
            }
        } else {
            $threadParticipants = MessageThreadParticipant::withTrashed()->where('thread_id', $thread->id);
            $participantTo = $threadParticipants->where('user_id', $userTo->ID)->first();
        }

        if ($message->Unread) {
            $participantTo->num_unread++;
            $participantTo->save();
        }

        $thread->num_messages++;
        $thread->last_message_id = $message->id;
        $thread->updated_at = $message->created_at;
        $thread->timestamps = false;
        $thread->save();

        $message->thread_id = $thread->id;
        $message->save();
    }
}
