<?php

declare(strict_types=1);

namespace App\Filament\Resources\OAuthClientResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\OAuthClientResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = OAuthClientResource::class;
}
