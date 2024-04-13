<?php

declare(strict_types=1);

namespace App\Support\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Exception;

class NoDeleteBelongsToMany extends BelongsToMany
{
    public function delete()
    {
        throw new Exception("Direct delete() on this relationship is not allowed. Use detach() instead.");
    }
}
