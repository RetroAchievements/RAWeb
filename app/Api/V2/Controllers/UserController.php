<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class UserController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\FetchRelated;
}
