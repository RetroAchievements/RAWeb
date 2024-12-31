<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Models\Emulator;
use App\Models\EmulatorUserAgent;

trait TestsEmulatorUserAgent
{
    protected string $userAgentValid = "MyClient/1.5";
    protected string $userAgentOutdated = "MyClient/1.2";
    protected string $userAgentBlocked = "MyClient/1.0";
    protected string $userAgentUnknown = "OtherClient/1.0";
    protected string $userAgentUnsupported = "TheirClient/1.0";

    protected function seedEmulatorUserAgents(): void
    {
        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Test Client'])->id,
            'client' => 'MyClient',
            'minimum_allowed_version' => '1.2',
            'minimum_hardcore_version' => '1.5',
        ]);

        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Their Client'])->id,
            'client' => 'TheirClient',
            'minimum_allowed_version' => null,
            'minimum_hardcore_version' => null,
        ]);
    }
}
