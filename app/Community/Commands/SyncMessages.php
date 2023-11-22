<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Models\Message;
use App\Community\Models\UserMessage;
use App\Community\Models\UserMessageChain;
use App\Site\Models\User;
use Illuminate\Console\Command;

class SyncMessages extends Command
{
    protected $signature = 'ra:sync:messages';
    protected $description = 'Sync messages';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $count = Message::whereNull('migrated_id')->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        // have to do this in batches to prevent exhausting memory
        // due to requesting payloads (message content)
        for ($i = 0; $i < $count; $i += 100) {
            $messages = Message::whereNull('migrated_id')->orderBy('ID')->limit(100)->get();
            /** @var Message $message */
            foreach ($messages as $message) {
                $this->migrateMessage($message);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }

    private function migrateMessage(Message $message)
    {
        $userFrom = User::where('User', $message->UserFrom)->first();
        $userTo = User::where('User', $message->UserTo)->first();
        if (!$userFrom || !$userTo) {
            // sender or recipient was deleted. ignore message
            $message->migrated_id = 0;
            $message->save();
            return;
        }

        if (strtolower(substr($message->Title, 0, 4)) == 're: ') {
            $parent = Message::where('Title', '=', substr($message->Title, 4))
                ->where('UserFrom', '=', $message->UserTo)
                ->where('UserTo', '=', $message->UserFrom)
                ->where('ID', '<', $message->ID)
                ->first();
        } else {
            $parent = null;
        }

        if ($parent === null) {
            $userMessageChain = new UserMessageChain([
                'title' => $message->Title,
                'sender_id' => $userFrom->ID,
                'recipient_id' => $userTo->ID,
            ]);
        } else {
            $migratedParent = UserMessage::where('id', $parent->migrated_id)->firstOrFail();
            $userMessageChain = UserMessageChain::where('id', $migratedParent->chain_id)->firstOrFail();
        }

        $userMessageChain->num_messages++;
        if ($message->Unread) {
            if ($userMessageChain->recipient_id == $userTo->ID) {
                $userMessageChain->recipient_num_unread++;
            } else {
                $userMessageChain->sender_num_unread++;
            }
        }
        $userMessageChain->save();

        $userMessage = new UserMessage([
            'chain_id' => $userMessageChain->id,
            'author_id' => $userFrom->ID,
            'body' => $message->Payload,
            'created_at' => $message->TimeSent,
        ]);
        $userMessage->save();

        $message->migrated_id = $userMessage->id;
        $message->save();
    }
}
