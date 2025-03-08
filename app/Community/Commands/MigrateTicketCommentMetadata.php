<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\Emulator;
use App\Models\EmulatorUserAgent;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\Ticket;
use App\Platform\Services\UserAgentService;
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
            $ticket = Ticket::with('achievement.game')->findOrFail($ticketId);

            $this->info('Updating metadata for ticket [' . $ticket->id . ']');

            $this->syncMetadataForTicket($ticket);
        } else {
            $count = Ticket::count();
            $this->info("Updating metadata for $count tickets.");
            $progressBar = $this->output->createProgressBar($count);

            $count = 0;
            Ticket::with('achievement.game')->chunkById(100, function ($tickets) use (&$count, $progressBar) {
                foreach ($tickets as $ticket) {
                    /** @var Ticket $ticket */
                    if ($this->syncMetadataForTicket($ticket)) {
                        $count++;
                    }
                }
                $progressBar->advance(100);
            });
            $progressBar->finish();

            $this->info("\n$count tickets have been updated.");
        }
    }

    private function syncMetadataForTicket(Ticket $ticket): bool
    {
        $changed = false;
        $newBody = '';
        $normalizedBody = str_replace("\\n", "\n",
                          str_replace("\\r", "\r",
                          str_replace("<br/>", "\n", $ticket->ReportNotes)));
        foreach (explode("\n", $normalizedBody) as $line) {
            if (str_starts_with($line, 'RetroAchievements Hash:')) {
                $hash = trim(substr($line, 23));
                $gameHash = GameHash::where('md5', '=', $hash)->first();
                if ($gameHash) {
                    $ticket->game_hash_id = $gameHash->id;
                    $changed = true;
                    continue;
                }
            } elseif (str_starts_with($line, 'MD5:')) {
                $hash = trim(substr($line, 4));
                $gameHash = GameHash::where('md5', '=', $hash)->first();
                if ($gameHash) {
                    $ticket->game_hash_id = $gameHash->id;
                    $changed = true;
                    continue;
                }
            } elseif (str_starts_with($line, 'Emulator:') || str_starts_with($line, 'Emulator used:')) {
                if ($line[8] === ':') {
                    $emulatorName = trim(substr($line, 9));
                } else {
                    $emulatorName = trim(substr($line, 14));
                }

                if (!empty($emulatorName)) {
                    $index = strpos($emulatorName, '(');
                    if ($index !== false) {
                        $ticket->emulator_core = substr($emulatorName, $index + 1, -1);
                        $emulatorName = substr($emulatorName, 0, $index - 1);
                    }

                    if ($emulatorName === "Other" && $ticket->emulator_id) {
                        // This ticket was already assigned to an emulator. Don't overwrite that.
                        return false;
                    }

                    $emulatorName = str_replace("Pizza Boy", "PizzaBoy", $emulatorName);
                    $emulatorName = str_replace("RAVisualBoyAdvance", "RAVBA", $emulatorName);
                    $emulatorName = str_replace("RAProject64", "RAP64", $emulatorName);
                    $emulatorName = str_replace("RAPCEngine", "RAPCE", $emulatorName);

                    $emulator = Emulator::where('name', $emulatorName)->first();
                    if ($emulator) {
                        $ticket->emulator_id = $emulator->id;
                        $changed = true;
                        continue;
                    }

                    $decoded = (new UserAgentService())->decode($emulatorName);
                    $emulator = Emulator::where('name', $decoded['client'])->first();
                    if ($emulator) {
                        $ticket->emulator_id = $emulator->id;
                        $ticket->emulator_version = $decoded['clientVersion'];
                        $changed = true;
                        continue;
                    }
                }
            } elseif (str_starts_with($line, 'Emulator Version:')) {
                if ($ticket->emulator_id) {
                    $ticket->emulator_version = trim(substr($line, 17));
                    $changed = true;
                    continue;
                }
            }

            $newBody .= $line;
            $newBody .= "\n";
        }

        $userAgentService = new UserAgentService();
        $userAgents = PlayerSession::where('user_id', $ticket->reporter_id)
            ->where('game_id', $ticket->achievement?->game?->id ?? 0)
            ->where('duration', '>', 5)
            ->whereNotNull('user_agent')
            ->distinct('user_agent')
            ->pluck('user_agent')
            ->toArray();
        $emulators = [];
        $emulatorVersion = null;
        $emulatorCore = null;
        foreach ($userAgents as $userAgent) {
            $decoded = $userAgentService->decode($userAgent);
            $emulatorUserAgent = EmulatorUserAgent::firstWhere('client', $decoded['client']);
            if ($emulatorUserAgent) {
                if (!in_array($emulatorUserAgent->emulator_id, $emulators)) {
                    $emulators[] = $emulatorUserAgent->emulator_id;
                    $emulatorVersion = $decoded['clientVersion'];
                    $emulatorCore = $decoded['clientVariation'] ?? null;
                }
            }
        }
        if (!empty($emulators)) {
            if (!$ticket->emulator_id || !in_array($ticket->emulator_id, $emulators)) {
                if (count($emulators) === 1) {
                    $ticket->emulator_id = $emulators[0];
                    $ticket->emulator_version = $emulatorVersion;
                    $ticket->emulator_core = $emulatorCore;
                    $changed = true;
                } elseif (count($emulators) > 1) {
                    $ticket->emulator_id = null;
                    $ticket->emulator_version = null;
                    $ticket->emulator_core = null;
                    $changed = true;
                }
            }
        }

        if ($changed) {
            if (!$ticket->emulator_id) {
                $ticket->emulator_version = null;
                $ticket->emulator_core = null;
            }

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
