<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\Emulator;
use App\Models\GameHash;
use App\Models\Ticket;
use Illuminate\Console\Command;

class MigrateTicketCommentMetadata extends Command
{
    protected $signature = "ra:community:ticket:migrate-comment-metadata
                            {ticketId? : Target a single ticket}";
    protected $description = "Migrate metadata from primary ticket comment to ticket fields";

    public function handle(): void
    {
        $ticketId = $this->argument('ticketId');

        if ($ticketId !== null) {
            $ticket = Ticket::findOrFail($ticketId);

            $this->info('Updating metadata for ticket [' . $ticket->id . ']');

            $this->syncMetadataForTicket($ticket);
        } else {
            $this->info('Updating metadata for ' . Ticket::count() . ' tickets.');

            $count = 0;
            Ticket::chunkById(100, function ($tickets) use(&$count) {
                foreach ($tickets as $ticket) {
                    if ($this->syncMetadataForTicket($ticket)) {
                        $count++;
                    }
                }
            });

            $this->info("$count tickets have been updated.");
        }
    }

    private function syncMetadataForTicket(Ticket $ticket): bool
    {
        $changed = false;
        $newBody = '';
        foreach (explode("\n", str_replace("<br/>", "\n", $ticket->ReportNotes)) as $line) {
            if (str_starts_with($line, 'RetroAchievements Hash:')) {
                $hash = trim(substr($line, 23));
                $gameHash = GameHash::where('md5', '=', $hash)->first();
                if ($gameHash) {
                    $ticket->game_hash_id = $gameHash->id;
                    $changed = true;
                    continue;
                }
            }
            elseif (str_starts_with($line, 'Emulator:')) {
                $emulatorName = trim(substr($line, 9));
                $index = strpos($emulatorName, '(');
                if ($index !== false) {
                    $ticket->emulator_core = substr($emulatorName, $index + 1, -1);
                    $emulatorName = substr($emulatorName, 0, $index - 1);
                }

                $emulator = Emulator::where('name', $emulatorName)->first();
                if ($emulator) {
                    $ticket->emulator_id = $emulator->id;
                    $changed = true;
                    continue;
                }
            }
            elseif (str_starts_with($line, 'Emulator Version:')) {
                $ticket->emulator_version = trim(substr($line, 17));
                $changed = true;
                continue;
            }
            elseif (str_starts_with($line, 'MD5:')) {
                $hash = trim(substr($line, 4));
                $gameHash = GameHash::where('md5', '=', $hash)->first();
                if ($gameHash) {
                    $ticket->game_hash_id = $gameHash->id;
                    $changed = true;
                    continue;
                }
            }
    
            $newBody .= $line;
            $newBody .= "\n";
        }

        if ($changed) {
            // use raw query to avoid updating Updated timestamp
            Ticket::where('id', $ticket->id)->update([
                'ReportNotes' => trim($newBody),
                'game_hash_id' => $ticket->game_hash_id,
                'emulator_id' => $ticket->emulator_id,
                'emulator_version' => $ticket->emulator_version,
                'emulator_core' => $ticket->emulator_core,
                'Updated' => $ticket->Updated,
            ]);
        }

        return $changed;
    }
}
