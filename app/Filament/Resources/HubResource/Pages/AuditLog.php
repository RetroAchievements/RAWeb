<?php

declare(strict_types=1);

namespace App\Filament\Resources\HubResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\HubResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = HubResource::class;
}
