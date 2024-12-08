<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Enums\ClientSupportLevel;
use App\Platform\Services\UserAgentService;

class GetClientSupportLevelAction
{
    public function execute(string $userAgent): ClientSupportLevel
    {
        $userAgentService = new UserAgentService();

        return $userAgentService->getSupportLevel($userAgent);
    }
}
