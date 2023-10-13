<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\GameHash;
use App\Platform\Models\System;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class NoIntroImport extends Command
{
    protected $signature = 'ra:platform:game-hash:no-intro:import {jsonDatFile} {systemId?} {--seed} {--ignore-system-mismatch}';
    protected $description = 'Imports JSON converted no-intro.org DAT files';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $systemId = $this->argument('systemId');
        $jsonDatFile = $this->argument('jsonDatFile');
        $ignoreSystemMismatch = $this->option('ignore-system-mismatch');
        $seed = $this->option('seed');

        $jsonDatFile = is_array($jsonDatFile) ? $jsonDatFile[0] : $jsonDatFile;
        $jsonDatFileContents = file_get_contents($jsonDatFile);
        if ($jsonDatFileContents === false) {
            return;
        }
        $jsonDat = json_decode($jsonDatFileContents, null, 512, JSON_THROW_ON_ERROR);

        /**
         * "header":{
         * "author":"alexian, kazumi213",
         * "date":"20181006-141240",
         * "description":"Nintendo - Super Nintendo Entertainment System (Parent-Clone)",
         * "name":"Nintendo - Super Nintendo Entertainment System (Parent-Clone)",
         * "url":"www.no-intro.org",
         * "version":"20181006-141240"
         * }
         */
        $header = $jsonDat->datafile->header;

        /** @var ?System $system */
        $system = null;
        if ($systemId) {
            $system = System::findOrFail($systemId);
        }

        if (!$system) {
            preg_match("/.* - ([^\(]*) \(.*/", $header->name, $matches);
            $system = System::where('name', $matches[1])
                ->orWhere('name_full', $matches[1])
                ->orWhere('name_short', $matches[1])
                ->first();
            if ($system) {
                if (!$this->output->confirm('Using detected system "' . $system->name . '" [' . $system->id . ']')) {
                    throw new Exception('Aborting.');
                }
            }
        }

        if (!$system) {
            throw new Exception('Aborting. No system ID given nor was a system detected successfully by name.');
        }

        $noIntroHashes = (new Collection($jsonDat->datafile->game))->filter(fn ($hash) => !empty($hash->rom));

        // dd($newRoms->filter(function ($hash) {
        //     return $hash->name !== $hash->description;
        // }));
        // dd($newRoms->filter(function ($hash) {
        //     return empty($hash->release);
        // }));

        $this->info('Importing ' . $noIntroHashes->count() . ' no-intro ROMs for ' . $system->name . ' from ' . $jsonDatFile);
        $existingHashes = GameHash::where('system_id', $system->id);
        $existingHashesCount = $existingHashes->count();
        $existingNoIntroHashes = $existingHashes->whereIn('hash', $noIntroHashes->pluck('rom.md5'));
        $existingNoIntroHashesCount = $existingNoIntroHashes->count();

        $this->info($existingHashesCount . ' existing ROMs found of which ' . $existingNoIntroHashesCount . ' are matching no-intro ROMs');

        /**
         * find hashes in other systems
         */
        $escapedHashes = GameHash::whereIn('hash', $noIntroHashes->pluck('rom.md5'))
            ->where('system_id', '<>', $system->id);
        if ($escapedHashes->count()) {
            $this->warn('Whoops! Seems some of those ROMs are attached to other systems:');
            $escapedHashes = $escapedHashes->get(['hash', 'system_id']);
            $this->warn($escapedHashes->pluck('system_id')->unique()->implode(', '));
            $this->warn($escapedHashes->pluck('hash')->unique()->implode(', '));
            if (!$ignoreSystemMismatch) {
                throw new Exception('System mismatch detected. Move those first or pass --ignore-system-mismatch.');
            }
        }

        if (!$existingNoIntroHashesCount) {
            $this->warn('Whoops! No existing ROMs found.');
            if (!$seed) {
                throw new Exception('Aborting. Pass --seed to seed this system - check if ROMs are suitable first!');
            }
        }

        $bar = $this->output->createProgressBar($noIntroHashes->count());
        $bar->setRedrawFrequency(10);
        foreach ($noIntroHashes as $noIntroHash) {
            $parentId = null;
            if ($noIntroHash->cloneof ?? null) {
                $parent = GameHash::where('name', $noIntroHash->cloneof)->first();
                if ($parent) {
                    $parentId = $parent->id;
                } else {
                    $this->error($noIntroHash->cloneof . ' is missing its parent');

                    return;
                }
            }

            // Kid Dracula NES seems to be very special...
            if (is_array($noIntroHash->rom)) {
                $noIntroHash->rom = $noIntroHash->rom[0];
            }

            $gameHash = GameHash::where('hash', $noIntroHash->rom->md5)
                ->where('system_id', $system->id)
                ->first();
            $gameHash ??= new GameHash();
            if ($noIntroHash->rom->name ?? null) {
                $gameHash->addFileName($noIntroHash->rom->name);
            }
            if ($noIntroHash->release->region ?? null) {
                $gameHash->addRegion($noIntroHash->release->region);
            }
            // TODO: depending on system the hash to be used might differ. usually it's the rom's md5 but that is not guaranteed
            $hash = $noIntroHash->rom->md5 ?? null ? mb_strtolower($noIntroHash->rom->md5) : null;

            if (!$hash) {
                $bar->advance();
                continue;
            }

            $gameHash->forceFill([
                'system_id' => $system->id,
                'parent_id' => $parentId,
                'hash' => $hash,
                'md5' => ($noIntroHash->rom->md5 ?? null) ? mb_strtolower($noIntroHash->rom->md5) : null,
                'crc' => ($noIntroHash->rom->crc ?? null) ? mb_strtolower($noIntroHash->rom->crc) : null,
                'sha1' => ($noIntroHash->rom->sha1 ?? null) ? mb_strtolower($noIntroHash->rom->sha1) : null,
                'name' => ($noIntroHash->name ?? null),
                'description' => $noIntroHash->description ?? null,
                'file_size' => $noIntroHash->rom->size ?? null,
                'source' => 'no-intro',
                'source_status' => $noIntroHash->rom->status ?? null,
                'source_version' => $header->version,
                'imported_at' => Carbon::now(),
            ]);
            $gameHash->save();
            $bar->advance();
        }
        $bar->finish();

        $this->info('Done');
    }
}
