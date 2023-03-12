<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use App\Support\Database\Eloquent\Concerns\HasFullTableName;
use Database\Factories\Legacy\UserActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class UserActivity extends BaseModel
{
    use HasFactory;
    use HasFullTableName;

    protected $table = 'Activity';

    public const CREATED_AT = 'timestamp';
    public const UPDATED_AT = 'lastupdate';

    protected $fillable = [
        'User',
        'timestamp',
        'lastupdate',
        'activitytype',
        'data',
        'data2',
    ];

    protected static function newFactory(): UserActivityFactory
    {
        return UserActivityFactory::new();
    }
}
