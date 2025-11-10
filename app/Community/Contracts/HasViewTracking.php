<?php

declare(strict_types=1);

namespace App\Community\Contracts;

use App\Models\User;

interface HasViewTracking
{
    public function markAsViewedBy(User $user): void;

    public function wasViewedBy(User $user): bool;
}
