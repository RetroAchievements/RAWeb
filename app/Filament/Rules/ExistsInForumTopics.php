<?php

declare(strict_types=1);

namespace App\Filament\Rules;

use App\Models\ForumTopic;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistsInForumTopics implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = ForumTopic::where('ID', $value)->exists();

        if (!$exists) {
            $fail('This forum topic ID does not exist.');
        }
    }
}
