<?php

declare(strict_types=1);

namespace App\Api\V2\GameInvites;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class RoleFilter implements Filter
{
    use DeserializesValue, IsSingular;

    /**
     * @var string
     */
    private string $name;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @return static
     */
    public static function make(string $name): self
    {
        return new static($name);
    }

    /**
     * RoleFilter constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        $user = request()->user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($value === 'sent') {
            return $query->where('sender_user_id', $user->id);
        }

        if ($value === 'received') {
            return $query->where('recipient_user_id', $user->id);
        }

        return $query;
    }
}
