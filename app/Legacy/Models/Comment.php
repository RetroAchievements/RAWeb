<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

/**
 * @property mixed $ArticleID
 */
class Comment extends BaseModel
{
    protected $table = 'Comment';
}
