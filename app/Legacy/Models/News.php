<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

/**
 * @property mixed $ID
 * @property mixed $Image
 * @property mixed $Payload
 * @property mixed $Timestamp
 * @property mixed $Title
 */
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
