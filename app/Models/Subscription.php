<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class Subscription extends BaseModel
{
    // TODO rename Subscription to subscriptions
    protected $table = 'Subscription';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';
}
