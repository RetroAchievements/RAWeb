<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Enums\ClientSupportLevel;
use App\Models\ConnectWarning;
use App\Models\Game;
use App\Platform\Services\UserAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait GeneratesConnectWarnings
{
    protected ?ConnectWarning $connectWarning = null;
    protected ClientSupportLevel $clientSupportLevel;

    public function handleRequest(Request $request): JsonResponse
    {
        $result = parent::handleRequest($request);

        if ($this->connectWarning !== null) {
            $this->connectWarning->save();
        }

        return $result;
    }

    protected function validateClient(Request $request, Game $game): void
    {
        $userAgentService = new UserAgentService();
        $this->clientSupportLevel = $userAgentService->getSupportLevel($this->userAgent);

        switch ($this->clientSupportLevel) {
            case ClientSupportLevel::Blocked:
                $this->addSmell($request, 'blocked_client');
                break;

            case ClientSupportLevel::Unknown:
                $this->addSmell($request, 'unknown_client');
                break;

            default:
                $emulatorUserAgent = $userAgentService->getEmulatorUserAgent($this->userAgent);
                if ($emulatorUserAgent) {
                    $supportedSystemCount = $emulatorUserAgent->emulator->systems()->count();
                    if ($supportedSystemCount > 0 && $supportedSystemCount < 5) {
                        // if the emulator only supports a handful of systems, and the game is not
                        // for one of those systems, flag it as suspicious.
                        if (!$emulatorUserAgent->emulator->systems()->where('system_id', $game->system_id)->exists()) {
                            $this->addSmell($request, 'wrong_client');
                        }
                    }
                }
                break;
        }
    }

    protected function addSmell(Request $request, string $smell): void
    {
        if (!$this->connectWarning) {
            $this->connectWarning = new ConnectWarning([
                'method' => $request->input('r'),
                'username' => $request->input('u') ?? '',
                'smells' => $smell,
                'user_agent' => $this->userAgent,
            ]);
        } else {
            $this->connectWarning->smells .= ',' . $smell;
        }
    }
}
