<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use LegacyApp\Support\Database\Eloquent\BaseModel;

class ForumTopic extends BaseModel
{
    protected $table = 'ForumTopic';

    public const CREATED_AT = 'DateCreated';
}
