<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Event;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Process\InvokedProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class RebuildAllSearchIndexes extends Command
{
    protected $signature = 'ra:search:rebuild {--concurrency=1 : Number of models to process in parallel (default: 1 for sequential)}';
    protected $description = 'Sync Scout index settings, flush all indexes, and re-import all searchable models';

    /**
     * @var array<class-string>
     */
    private array $searchableModels = [
        Achievement::class,
        Comment::class,
        Event::class,
        ForumTopicComment::class,
        Game::class,
        GameSet::class,
        User::class,
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Starting search index rebuild...');

        $this->syncIndexSettings();
        $this->flushIndexes();
        $this->importModels();

        $this->newLine();
        $this->components->info('Search index rebuild jobs dispatched successfully!');

        return self::SUCCESS;
    }

    private function syncIndexSettings(): void
    {
        $this->components->info('Syncing index settings...');
        Artisan::call('scout:sync-index-settings', [], $this->output);
    }

    private function flushIndexes(): void
    {
        $this->newLine();
        $this->components->info('Flushing all indexes...');

        $this->runParallelCommands('scout:flush');
    }

    private function importModels(): void
    {
        $this->newLine();
        $this->components->info('Importing models...');

        $this->runParallelCommands('scout:import');
    }

    private function runParallelCommands(string $command): void
    {
        $concurrency = max(1, (int) $this->option('concurrency'));
        $modelChunks = array_chunk($this->searchableModels, $concurrency);

        foreach ($modelChunks as $models) {
            $this->processModelBatch($command, $models);
        }
    }

    /**
     * @param array<class-string> $models
     */
    private function processModelBatch(string $command, array $models): void
    {
        /** @var array<string, InvokedProcess> $processes */
        $processes = [];

        // Start processes for this batch.
        foreach ($models as $model) {
            $shortName = class_basename($model);
            $processes[$shortName] = Process::path(base_path())
                ->timeout(1800)
                ->start(['php', 'artisan', $command, $model]);
        }

        // Poll processes for output while any are still running.
        while (count(array_filter($processes, fn ($p) => $p->running())) > 0) {
            foreach ($processes as $shortName => $process) {
                if (!$process->running()) {
                    continue;
                }

                $output = $process->latestOutput();
                if ($output !== '') {
                    $lines = array_filter(explode("\n", trim($output)));
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line !== '') {
                            $this->line("  <comment>[{$shortName}]</comment> {$line}");
                        }
                    }
                }
            }

            usleep(100000); // 100ms polling interval.
        }
    }
}
