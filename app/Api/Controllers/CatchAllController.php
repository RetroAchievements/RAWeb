<?php

declare(strict_types=1);

namespace App\Api\Controllers;

use App\Http\Controller;
use Illuminate\Http\Request;

class CatchAllController extends Controller
{
    /**
     * Handle undefined API routes.
     */
    public function handle(Request $request, ?string $method = null): void
    {
        abort(404, 'API endpoint not found');
    }
}
