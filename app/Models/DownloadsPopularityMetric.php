<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class DownloadsPopularityMetric extends BaseModel
{
    protected $table = 'downloads_popularity_metrics';

    protected $fillable = [
        'key',
        'ordered_ids',
    ];

    protected $casts = [
        'ordered_ids' => 'array',
    ];
}
