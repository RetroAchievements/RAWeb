<?php

declare(strict_types=1);

namespace App\Api\V2\MessageThreads;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;

class IsUnreadFilter implements Filter
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function key(): string
    {
        return $this->name;
    }

    public function isSingular(): bool
    {
        return false; // This filter can return multiple threads
    }

    public function apply($query, $value): Builder
    {
        $value = (string) $value;

        if ($value === 'true' || $value === '1') {
            $user = request()->user();
            if ($user) {
                $query->whereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->where('num_unread', '>', 0);
                });
            }
        }

        return $query;
    }

    public function validate($value): bool
    {
        return true; // Accept any value for now
    }
}
