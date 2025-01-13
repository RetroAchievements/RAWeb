<?php

namespace App\Filament\Resources\GameHashResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameHashResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameHashResource::class;
}
