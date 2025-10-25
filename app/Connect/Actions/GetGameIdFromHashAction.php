<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Enums\ClientSupportLevel;
use App\Platform\Services\UserAgentService;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class GetGameIdFromHashAction extends BaseApiAction
{
    protected string $hash;

    public function execute(string $hash): array
    {
        $this->hash = $hash;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['m'])) {
            return $this->missingParameters();
        }

        $this->hash = request()->input('m', '');

        // if a client has been explicitly blocked, prevent hash resolution so the client
        // is never able to retrieve the runtime assets.
        // unknown and outdated clients are still allowed to unlock stuff in softcore, so
        // don't block them.
        $userAgentService = new UserAgentService();
        $clientSupportLevel = $userAgentService->getSupportLevel(request()->header('User-Agent'));
        if ($clientSupportLevel === ClientSupportLevel::Blocked) {
            $error = $this->unsupportedClient();
            $error['GameID'] = 0; // include "no match" for clients not checking the error state

            return $error;
        }

        return null;
    }

    protected function process(): array
    {
        return [
            'Success' => true,
            'GameID' => VirtualGameIdService::idFromHash($this->hash),
        ];
    }
}
