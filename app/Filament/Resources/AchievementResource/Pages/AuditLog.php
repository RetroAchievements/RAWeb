<?php

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementResource::class;
}
