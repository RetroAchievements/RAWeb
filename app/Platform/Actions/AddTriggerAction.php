<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use Illuminate\Http\Request;

class AddTriggerAction
{
    public function execute(Request $request, Achievement $achievement, string $trigger): void
    {
    }
}
