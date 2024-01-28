<?php

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\SystemResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = SystemResource::class;
}
