<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

/**
 * @property string $Author
 * @property int $ID
 * @property string $Title
 */
class ForumTopic extends BaseModel
{
    protected $table = 'ForumTopic';

    public const CREATED_AT = 'DateCreated';
}
