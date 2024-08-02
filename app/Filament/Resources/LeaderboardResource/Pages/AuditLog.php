<?php

namespace App\Filament\Resources\LeaderboardResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\LeaderboardResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = LeaderboardResource::class;
}
