<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Connect\Actions\IdentifyGameHashAction;
use App\Enums\GameHashCompatibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\TestCase;

class GameIdTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsEmulatorUserAgent;

    public function testUnknownGameId(): void
    {
        $this->get($this->apiUrl('gameid', ['m' => 'ABCDEF0123456789']))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => 0,
            ]);
    }

    public function testValidGameId(): void
    {
        $game = $this->seedGame();

        $this->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'GameID' => $game->id,
                'Success' => true,
            ]);
    }

    public function testIncompatibleHash(): void
    {
        $game = $this->seedGame();
        $gameHash = $game->hashes()->first();

        $gameHash->compatibility = GameHashCompatibility::Incompatible;
        $gameHash->save();

        $this->get($this->apiUrl('gameid', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'GameID' => $game->id + IdentifyGameHashAction::IncompatibleIdBase,
                'Success' => true,
            ]);

        $gameHash->compatibility = GameHashCompatibility::Untested;
        $gameHash->save();

        $this->get($this->apiUrl('gameid', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'GameID' => $game->id + IdentifyGameHashAction::UntestedIdBase,
                'Success' => true,
            ]);

        $gameHash->compatibility = GameHashCompatibility::PatchRequired;
        $gameHash->save();

        $this->get($this->apiUrl('gameid', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'GameID' => $game->id + IdentifyGameHashAction::PatchRequiredIdBase,
                'Success' => true,
            ]);
        
        $gameHash->compatibility = GameHashCompatibility::Compatible;
        $gameHash->save();
    
        $this->get($this->apiUrl('gameid', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'GameID' => $game->id,
                'Success' => true,
            ]);
    }

    public function testUserAgent(): void
    {
        $game = $this->seedGame();

        $this->seedEmulatorUserAgents();

        // no user agent
        $this->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
            ]);

        // unknown user agent
        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
            ]);

        // unsupported user agent
        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
            ]);

        // blocked user agent
        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Success' => false,
                'Error' => 'This emulator is not supported',
                'GameID' => 0,
            ]);

        // valid user agent (outdated should be treated as valid)
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('gameid', ['m' => $game->hashes()->first()->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
            ]);
    }
}
