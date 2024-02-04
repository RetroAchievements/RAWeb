<?php

declare(strict_types=1);

namespace App\Support\Sync;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

trait SyncTrait
{
    protected string $kind;
    protected string|array $uniqueKey;

    protected array $keyMap;
    protected array $unhandledKeys = [];

    protected string $modelClass;
    protected Model $model;
    protected string $table;

    protected string $referenceModelClass;
    protected Model $referenceModel;
    protected string $referenceTable;

    protected string $referenceKey;
    protected ?string $referenceColumn = null;

    protected array $userIds = [];

    protected array $require;

    protected SyncStatus $syncStatus;
    protected ?SyncEntity $lastReference = null;

    protected bool $incremental = true;
    protected string $strategy;

    protected ?array $postSyncCommands = null;

    /**
     * @throws Exception
     */
    protected function sync(string $kind): void
    {
        $this->kind($kind);

        // determine how up-to-date the dependent tables are.
        // don't sync anything newer than the latest dependency update.
        $syncTime = Carbon::now();
        foreach ($this->require as $require) {
            if (!$this->checkRequiredSync($require, $syncTime)) {
                return;
            }
        }

        $start = microtime(true);

        $message = 'Syncing ' . $this->kind . ' older than ' . $syncTime . ' with ' . $this->strategy . ' strategy';
        $this->line($message);

        $entitiesStoredTotal = 0;
        $entitiesSkippedTotal = 0;

        $query = $this->query();

        /**
         * for special cases where the column is from a sub-select use a referenceColumn
         */
        $referenceColumn = $this->referenceColumn ?: $this->referenceTable . '.' . $this->referenceKey;

        $query->orderBy($referenceColumn);

        // capture a copy of the query in case we need to split it up
        $baseQuery = clone $query;

        $this->appendWhereClauses($query, $referenceColumn, $syncTime, 0);
        $total = $query->count();

        $bar = $this->output->createProgressBar($total);
        $bar->setRedrawFrequency(100);
        $bar->setFormat('debug');
        $bar->start();

        // DB::disableQueryLog();
        // Model::unguard();

        // if there are more than 1.5x the specified number of rows, attempt to process
        // the query in batches so as not to overload the system
        $chunkSize = 1_000_000;
        while ($total - $entitiesStoredTotal > $chunkSize * 3 / 2) {
            // find the value of the referenceKey for the ~1,000,000th row
            $cutoff = $query->skip($chunkSize)->take(1)->get()->first()->{$this->referenceKey};

            // reset the query and only return values up to the cutoff. the exact
            // number of rows returned may exceed the chunkSize, but guarantees that
            // we get all entries with the cutoff value in this chunk. using query->chunk()
            // may duplicate or miss entries as it is indeterminate if values with the same
            // referenceKey will be chunked into the first or second chunk.
            // this behaves like a partial sync and allows us to keep the memory down when
            // syncing very large tables, but updates the progress bar to simulate a full sync.
            $query = clone $baseQuery;
            $this->appendWhereClauses($query, $referenceColumn, $syncTime, $entitiesStoredTotal);
            if ($cutoff) {
                $query->where($referenceColumn, '<=', $cutoff);

                // make sure to include null records in the first batch or they'll get excluded
                // by the comparison
                if (!$this->lastReference && !$this->syncStatus->reference) {
                    $query->orWhereNull($referenceColumn);
                }
            } else {
                $query->whereNull($referenceColumn);
            }

            $this->syncEntities($query, $bar, $total, $entitiesStoredTotal, $entitiesSkippedTotal);

            // reset the query for the next loop or the final processing
            $query = clone $baseQuery;
            $this->appendWhereClauses($query, $referenceColumn, $syncTime, $entitiesStoredTotal);
        }

        $this->syncEntities($query, $bar, $total, $entitiesStoredTotal, $entitiesSkippedTotal);

        // only update sync state if it's an incremental run - not when it's a specific entry to be synced
        if ($this->incremental) {
            $this->updateSyncStatus($total - $entitiesStoredTotal);
        }

        // Model::unguard(false);

        $bar->finish();
        $this->line('');

        if (!empty($this->unhandledKeys)) {
            $message = 'Unhandled keys found in ' . $this->kind . ': ' . implode(',', array_keys($this->unhandledKeys));
            $this->warn($message);
        }

        $time_elapsed_secs = microtime(true) - $start;
        $message = 'Synced ' . number_format($entitiesStoredTotal - $entitiesSkippedTotal) . ' ' . $this->kind . ' entities '
            . 'with ' . mb_strtoupper($this->strategy) . ' strategy '
            . 'in ' . $time_elapsed_secs . ' seconds';
        $this->info($message);
        if ($entitiesStoredTotal) {
            Log::debug($message);
        } else {
            $this->warn('Nothing to sync -> back off');

            // update updated_at timestamp so dependencies can query more recent data
            $this->syncStatus->touch();
        }

        if ($entitiesSkippedTotal) {
            $message = ' ' . number_format($entitiesSkippedTotal) . ' ' . $this->kind . ' entities were skipped.';
            $this->info($message);
            Log::debug($message);
        }
    }

    protected function appendWhereClauses(Builder &$query, string $referenceColumn, Carbon $syncTime, int $entitiesStoredTotal): void
    {
        if ($this->hasArgument('id') && $this->argument('id')) {
            $this->line(' only for ID ' . $this->argument('id'));
            $this->incremental = false;
            $query->where($this->referenceTable . '.ID', $this->argument('id'));
        }

        if ($this->hasArgument('gameid') && $this->argument('gameid')) {
            $this->line(' only for game ID ' . $this->argument('gameid'));
            $this->incremental = false;
            $query->where($this->referenceTable . '.GameID', $this->argument('gameid'));
        }

        if ($this->hasArgument('username') && $this->argument('username')) {
            $this->line(' only for user ' . $this->argument('username'));
            $this->incremental = false;
            $query->where($this->referenceTable . '.User', $this->argument('username'));
        }

        // if $entitiesStoredTotal is not 0, the query has been chunked and this is a continuation.
        // only look for stuff > syncStatus (or not null if first chunk was only nulls)
        if ($entitiesStoredTotal || ($this->incremental && !$this->option('full'))) {
            if ($this->syncStatus->reference) {
                $query->where($referenceColumn, '>', $this->syncStatus->reference);
                $query->where($referenceColumn, '<', $syncTime);
            } elseif ($entitiesStoredTotal) {
                $query->whereNotNull($referenceColumn);
            }
        }
    }

    protected function syncEntities(
        Builder $query,
        ProgressBar &$bar,
        int $total,
        int &$entitiesStoredTotal,
        int &$entitiesSkippedTotal
    ): void {
        // NOTE: MySQL buffers the entire resultset into memory when using cursor(), so this may consume a lot
        //       of memory if there are a lot of rows. see https://github.com/laravel/framework/issues/14919
        //       we try to work around this by doing our own chunking in sync()
        $entities = $query->cursor();
        if (!is_a($entities, LazyCollection::class)) {
            return;
        }

        // use DB transactions to batch 5000 records at a time. this greatly speeds up the synchronization
        $transactionSize = 5000;
        $transactionCount = 0;
        try {
            foreach ($entities as $entity) {
                $transformed = $this->transformEntity($entity);

                if ($transactionCount >= $transactionSize) {
                    // can only commit the transaction if the referenceKey value changes.
                    // otherwise, we might lose data if the next transaction is aborted.
                    if (!$this->lastReference || $this->lastReference->reference != $transformed->reference) {
                        // only update sync state if it's an incremental run - not when it's a specific entry to be synced
                        if ($this->incremental) {
                            $this->updateSyncStatus($total - $entitiesStoredTotal);
                        }

                        DB::commit();
                        DB::beginTransaction();
                        $transactionCount = 0;
                    }
                } elseif ($transactionCount == 0) {
                    DB::beginTransaction();
                }
                $transactionCount++;

                $transformed->data = $this->preProcessEntity($entity, $transformed->data);

                if (empty($transformed->data)) {
                    // empty data indicates the sync command wishes to ignore this record
                    $entitiesSkippedTotal++;
                } else {
                    $this->storeEntity($transformed);

                    if (!$this->option('no-post')) {
                        $this->postProcessEntity($entity, (object) $transformed->data);
                    }
                }

                $entitiesStoredTotal++;
                $bar->advance();
            }

            if ($transactionCount > 0) {
                // only update sync state if it's an incremental run - not when it's a specific entry to be synced
                if ($this->incremental) {
                    $this->updateSyncStatus($total - $entitiesStoredTotal);
                }

                DB::commit();
            }
        } catch (Exception $e) {
            if ($transactionCount > 0) {
                DB::rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param Collection<int, object> $entities
     * @return Collection<int, SyncEntity>
     * @throws Exception
     */
    protected function transform(Collection $entities): Collection
    {
        if (!$this->keyMap) {
            throw new Exception('No key map for ' . $this->kind);
        }

        return $entities->map(fn ($entity) => $this->transformEntity($entity));
    }

    protected function transformEntity(object $entity): SyncEntity
    {
        $referenceValue = $entity->{$this->referenceKey};
        $entity = (new Collection($entity))
            ->filter(function ($value, $key) {
                if (empty($this->keyMap[$key])) {
                    $this->unhandledKeys[$key] = true;

                    return false;
                }

                return true;
            })
            ->mapWithKeys(function ($value, $key) {
                if ($key == 'user_id') {
                    $value = $this->getUserId($value);
                }
                if ($this->keyMap[$key]['fixEncoding'] ?? false) {
                    $value = $this->fixEncoding($value);
                }

                /*
                 * anything empty should be null
                 */
                if (empty($value)) {
                    $value = null;
                }

                switch ($this->keyMap[$key]['type'] ?? false) {
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'timestamp':
                        $value = Carbon::parse($value)->format('j M Y G:i:s.u');
                        break;
                }

                return [
                    $this->keyMap[$key]['key'] => $value,
                ];
            });

        return new SyncEntity((string) $referenceValue, $entity->toArray());
    }

    protected function primaryKeyName(): string
    {
        return $this->model->getKeyName();
    }

    /**
     * @throws Exception
     */
    protected function kind(string $kind): void
    {
        $this->kind = $kind;

        if (!config('sync.kinds.' . $this->kind)) {
            throw new Exception('Kind ' . $this->kind . ' is not supported');
        }

        $this->require = config('sync.kinds.' . $this->kind . '.require', []);

        $this->modelClass = config('sync.kinds.' . $this->kind . '.model');
        $this->model = new $this->modelClass();
        $this->table = $this->model::getFullTableName();

        $this->referenceModelClass = config('sync.kinds.' . $this->kind . '.reference_model');
        $this->referenceModel = new $this->referenceModelClass();
        $this->referenceTable = $this->referenceModel::getFullTableName();

        $this->referenceKey = config('sync.kinds.' . $this->kind . '.reference_key');
        $this->referenceColumn = config('sync.kinds.' . $this->kind . '.reference_column');
        $this->uniqueKey = config('sync.kinds.' . $this->kind . '.unique_key');
        $this->keyMap = config('sync.kinds.' . $this->kind . '.map');

        $this->postSyncCommands = config('sync.kinds.' . $this->kind . '.post-sync-commands');

        $this->strategy = config('sync.kinds.' . $this->kind . '.strategy', SyncStrategy::UPSERT);

        /** @var SyncStatus $syncStatus */
        $syncStatus = SyncStatus::firstOrCreate(['kind' => $this->kind]);
        $this->syncStatus = $syncStatus;
    }

    protected function query(): Builder
    {
        return DB::table($this->referenceTable);
    }

    /**
     * @param Collection<int, SyncEntity> $entities
     */
    protected function store(Collection $entities): void
    {
        $this->line('Storing chunk of ' . $entities->count() . ' items');
        $data = $entities->pluck('data')->toArray();
        $this->lastReference = $entities->last();
        $this->storeByStrategy($data);
    }

    protected function storeEntity(SyncEntity $entity): void
    {
        $this->lastReference = $entity;
        $this->storeByStrategy($entity->data);
    }

    /**
     * Return an empty array to skip the entry.
     */
    protected function preProcessEntity(object $origin, array $transformed): array
    {
        return $transformed;
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
    }

    private function storeByStrategy(array $data): void
    {
        switch ($this->strategy) {
            case SyncStrategy::INSERT_IGNORE:
                $this->model->getConnection()->table($this->model->getTable())->insertOrIgnore($data);
                break;
            case SyncStrategy::UPSERT:
            default:
                $this->model->getConnection()->table($this->model->getTable())->upsert($data, $this->uniqueKey);
                break;
        }
    }

    protected function updateSyncStatus(int $remaining): void
    {
        /*
         * update sync state
         */
        if ($this->lastReference) {
            $this->syncStatus->reference = $this->lastReference->reference;
        }

        $this->syncStatus->remaining = $remaining;
        $this->syncStatus->save();
    }

    protected function checkRequiredSync(string $required, Carbon &$syncTime, int $threshold = 0): bool
    {
        /** @var ?SyncStatus $syncState */
        $syncState = SyncStatus::find($required);

        if ($syncState === null
            || !$syncState->reference
            || $syncState->remaining > $threshold
        ) {
            $this->warn($required . ' not fully synced yet. Aborting');

            return false;
        }

        // can only sync up to the last sync time of a depedendant record. newer local
        // records could be referencing unsynced records from the parent table.
        // NOTE: updated is when the sync finished for that record, we could still be
        //       missing references to whatever records were created during the sync
        if ($syncState->updated < $syncTime) {
            $syncTime = $syncState->updated;
        }

        // also check the last sync time of the dependent record's dependency as it would
        // not have sync'd past that point.
        $dependentRequires = config('sync.kinds.' . $required . '.require', []);
        foreach ($dependentRequires as $require) {
            if (!$this->checkRequiredSync($require, $syncTime, $threshold)) {
                return false;
            }
        }

        return true;
    }

    /**
     * remove all non utf8 characters
     */
    protected function fixEncoding(string $value): string
    {
        /**
         * we are not afraid of html entities
         */
        $value = html_entity_decode($value, ENT_COMPAT | ENT_QUOTES, 'UTF-8');

        /*
         * we are afraid of invalid utf8 characters though
         * strip all invalid utf8 characters
         */
        ini_set('mbstring.substitute_character', 'none');
        $value = mb_convert_encoding($value, 'utf-8', 'utf-8');

        /**
         * some encoding tests
         */
        // dd("ÑÐ½ Ð¢Ð°Ñ€Ð¾ Ð¢Ð°ÑÑÐ°Ð");
        // $input = $transformed->data['motto'];
        // $input = "Ã‘ÂÃÂ½ ÃÂ¢ÃÂ°Ã‘â‚¬ÃÂ¾ ÃÂ¢ÃÂ°Ã‘ÂÃ‘ÂÃÂ°ÃÂ";
        // $input = "き ki, ひ hi, み mi, け ke, へ he, め me";
        // dump($input);
        // dd("ÑÐ½ Ð¢Ð°Ñ€Ð¾ Ð¢Ð°ÑÑÐ°Ð");
        // $transformed->data['motto'] = $input;
        // dd($transformed->data['motto']);
        // dd(utf8_decode($input));
        // $input = "@area(0x1bc43a) Exploring Kattelox Island [âš–: @Difficulty(0xh1bc440)] [ðŸ’°: @Zenny(0xL1bc404*10_0xU1bc404*160_0xL1bc405*2560_0xU1bc405*40960_0xL1bc406*655360_0xU1bc406*10485760_0xL1bc407*167772160_0xU1bc407*2684354560)] [â°: @Time(0x1bc3f6*131072_0x1bc3f4*2)] [ðŸ”“: @Digit(0xS205633_0xT205633_0xO20562a_0xR20562a_0xS20562a_0xM20562b_0xS20562b_0xO20562b_0xQ20562b_0xP20562b_0xR20562b_0xS20560c_0xR205633_0xQ205633_0xS205637_0xN205633_0xP205631_0xP20560d_0xQ20560c_0xM205630_0xT205637_0xN20560d_0xO20560c_0xR205637_0xT20560c_0xO20560d_0xR20560c_0xM20560c_0xQ20560d_0xR205631_0xQ205631_0xS205632_0xR205632_0xP205630_0xM205633_0xO205632_0xN205632_0xM20560d_0xP20560c_0xQ205630_0xO205630_0xN205630_0xS205628_0xQ205628_0xN20562a_0xM20562a_0xS205629_0xM205629_0xN205629_0xR20560f_0xT20560f_0xO205633_0xP205633_0xS20560f_0xQ20560f_0xT205632_0xN20560f_0xT20560e_0xP205631_0xP20560f_0xO20560f_0xN205631_0xO205631_0xM205631_0xM20560f_0xR205612_0xS20560e_0xN20560e_0xQ205632_0xT205630_0xQ20560e_0xN20560e_0xR20560e_0xP20560e_0xP205632_0xS205630_0xR205630_0xT20560d_0xM205632_0xS20560d_0xM20560e_0xR20560d_0xT205631_0xN205637_0xO205637_0xP205637_0xQ205637)/87]";
        // $input = "Ã‘ÂÃÂ½ ÃÂ¢ÃÂ°Ã‘â‚¬ÃÂ¾ ÃÂ¢ÃÂ°Ã‘ÂÃ‘ÂÃÂ°ÃÂ";
        // $input = mb_convert_encoding($input, 'UTF-8', 'ascii');
        // $input = str_replace(' ', '', $input);
        // $input = utf8_decode($input);
        // dd($input);
        // $str = iconv('UTF-8', 'ASCII//TRANSLIT', "Côte d'Ivoire");
        // dd($str);
        // dd(utf8_encode(mb_convert_encoding($input, 'ascii', 'UTF-8')));
        // dd(mb_convert_encoding('utf-8', 'utf-8//IGNORE', $transformed->data['motto']));

        /**
         * from https://www.i18nqa.com/debug/utf8-debug.html
         * NOTE: converting from Windows-1252 to utf-8 here might introduce additional utf-8 conflicts (in postgres, mysql somehow eats that)
         * some chars have been encoded multiple times like this: ÃƒÆ’Ã‚Â© which is é encoded three times
         * we cannot know how often some of those were encoded to revert it just as often to not end up with remaining sequences
         * additionally, converting here would remove some valid utf-8 characters, too
         * manually replace those instead (see below) or ignore
         */
        // if (mb_strpos($value, 'â€™') !== false
        //     || mb_strpos($value, 'Ãª') !== false
        //     || mb_strpos($value, 'Ãƒ') !== false
        //     || mb_strpos($value, 'Â©') !== false
        // ) {
        //     $value = mb_convert_encoding($value, 'Windows-1252', 'utf-8');
        // }

        /**
         * replace some repeated encodings from previous server migrations
         */
        $value = str_replace('ÃƒÆ’Ã‚Â©', 'é', $value);
        $value = str_replace('ÃƒÂ©', 'é', $value);
        $value = str_replace('â€™', '’', $value);
        $value = str_replace('Ãª', 'ª', $value);
        $value = str_replace('Ãƒ', 'Ã', $value);

        return $value;
    }

    /**
     * get the unique identifier for a username
     */
    protected function getUserId(string $username): ?int
    {
        $userId = $this->userIds[$username] ?? null;
        if ($userId === null) {
            /** @var ?User $user */
            $user = User::where('User', Str::lower($username))->first();
            if (!$user) {
                $this->userIds[$username] = 0;

                return null;
            }

            $this->userIds[$username] = $userId = $user->ID;
        }

        return ($userId > 0) ? $userId : null;
    }
}
