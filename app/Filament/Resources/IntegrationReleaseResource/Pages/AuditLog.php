<?php

namespace App\Filament\Resources\IntegrationReleaseResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\IntegrationReleaseResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = IntegrationReleaseResource::class;
}
