<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Http\Controller;
use Illuminate\Http\Request;

class WebApiController extends Controller
{
    public function noop(Request $request, ?string $method = null): void
    {
        abort(405, 'Method not allowed');
    }
}
