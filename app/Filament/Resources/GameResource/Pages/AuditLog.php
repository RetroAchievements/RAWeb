<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;
}
