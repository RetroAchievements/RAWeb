<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnrankedUser extends BaseModel
{
    protected $table = 'unranked_users';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
    ];

    /**
     * @return BelongsTo<User, UnrankedUser>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }
}
