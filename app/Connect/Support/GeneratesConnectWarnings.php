<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Enums\ClientSupportLevel;
use App\Models\ConnectWarning;
use App\Models\Game;
use App\Models\System;
use App\Platform\Services\UserAgentService;
use App\Support\Alerts\SuspiciousConnectWarningAlert;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait GeneratesConnectWarnings
{
    protected ?ConnectWarning $connectWarning = null;
    protected ClientSupportLevel $clientSupportLevel;

    public function handleRequest(Request $request): JsonResponse
    {
        $result = parent::handleRequest($request);

        if ($this->connectWarning !== null) {
            $this->finalizeWarning();
            $this->connectWarning->save();
            $this->sendNotifications();
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
                if ($emulatorUserAgent && $game->system_id !== System::Events) {
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
                'user_agent' => $this->userAgent ?? '',
            ]);
        } else {
            $this->connectWarning->smells .= ',' . $smell;
        }
    }

    private function finalizeWarning(): void
    {
        if ($this->connectWarning->related_id && str_contains($this->connectWarning->smells, 'bad_validation')) {
            $isRepeated = ConnectWarning::query()
                ->where('method', $this->connectWarning->method)
                ->where('username', $this->connectWarning->username)
                ->where('related_type', $this->connectWarning->related_type)
                ->where('related_id', '!=', $this->connectWarning->related_id)
                ->where('validation_hash', $this->connectWarning->validation_hash)
                ->exists();
            if ($isRepeated) {
                $this->connectWarning->smells .= ',repeated_validation';
            }
        }
    }

    private function sendNotifications(): void
    {
        if (str_contains($this->connectWarning->smells, 'repeated_validation')
            || str_contains($this->connectWarning->smells, 'wrong_client')) {

            // only send one notification per user per day
            $key = 'user:' . strtolower($this->connectWarning->username) . ':connect_warning_notification';
            if (Cache::add($key, '1', Carbon::now()->addDay())) {
                (new SuspiciousConnectWarningAlert($this->connectWarning))->send();
            }
        }
    }
}
