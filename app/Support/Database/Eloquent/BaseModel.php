<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent;

use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasFullTableName;
}
