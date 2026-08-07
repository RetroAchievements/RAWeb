<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Support\WhitespaceNormalizer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NormalizeTextWhitespace extends Command
{
    protected $signature = 'ra:platform:normalize-text-whitespace {--dry-run : Report what would change without writing}';
    protected $description = 'Collapses invisible whitespace in existing game, achievement, and leaderboard text';

    /**
     * @var array<class-string<Model>, list<string>>
     */
    private const COLUMNS = [
        Game::class => ['title', 'developer', 'publisher', 'genre', 'legacy_guide_url'],
        Achievement::class => ['title', 'description'],
        Leaderboard::class => ['title', 'description'],
    ];

    public function handle(): void
    {
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Dry run. No rows will be written.');
        }

        foreach (self::COLUMNS as $modelClass => $attributes) {
            $this->normalizeModel($modelClass, $attributes, $isDryRun);
        }
    }

    /**
     * @param class-string<Model> $modelClass
     * @param list<string> $attributes
     */
    private function normalizeModel(string $modelClass, array $attributes, bool $isDryRun): void
    {
        $this->info(sprintf('Scanning %s (%s)...', class_basename($modelClass), implode(', ', $attributes)));

        $changed = 0;

        $modelClass::query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->chunkById(1000, function ($records) use ($attributes, $isDryRun, &$changed): void {
                foreach ($records as $record) {
                    $updates = [];

                    foreach ($attributes as $attribute) {
                        $value = $record->getAttribute($attribute);

                        if (!is_string($value) || !WhitespaceNormalizer::hasInvisible($value)) {
                            continue;
                        }

                        $normalized = WhitespaceNormalizer::normalize($value);

                        if ($normalized !== $value) {
                            $updates[$attribute] = $normalized;

                            $this->line(sprintf('  %s %s: %s', $record->getKey(), $attribute, json_encode($value)));
                        }
                    }

                    if ($updates) {
                        $changed++;

                        if (!$isDryRun) {
                            $record->forceFill($updates)->save();
                        }
                    }
                }
            });

        $this->info(sprintf('%s: %d rows %s.', class_basename($modelClass), $changed, $isDryRun ? 'would change' : 'changed'));
        $this->newLine();
    }
}
