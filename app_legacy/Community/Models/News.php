<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class News extends BaseModel
{
    protected $table = 'News';

    public const CREATED_AT = 'Timestamp';

    protected $fillable = [
        'Title',
        'Payload',
        'Author',
        'Link',
        'Image',
    ];
}
