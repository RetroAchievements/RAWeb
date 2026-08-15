<?php

declare(strict_types=1);

namespace App\Filament\Resources\ModerationWatchlistTermResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\ModerationWatchlistTermResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = ModerationWatchlistTermResource::class;
}
