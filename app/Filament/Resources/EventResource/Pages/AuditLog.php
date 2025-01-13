<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\EventResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = EventResource::class;
}
