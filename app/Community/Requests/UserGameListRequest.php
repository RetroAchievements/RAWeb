<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Platform\Requests\GameListRequest;

class UserGameListRequest extends GameListRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
