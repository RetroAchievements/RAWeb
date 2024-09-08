<?php

namespace App\Filament\Resources\EmulatorResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\EmulatorResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = EmulatorResource::class;
}
