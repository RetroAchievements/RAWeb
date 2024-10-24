<?php

namespace App\Filament\Resources\AchievementSetResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementSetResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementSetResource::class;
}
