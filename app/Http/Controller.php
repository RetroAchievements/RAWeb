<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\Concerns\HandlesResources;
use App\Support\Concerns\ResolvesSlugs;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    use HandlesResources;
    use ResolvesSlugs;
}
