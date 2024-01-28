<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\UserResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = UserResource::class;
}
