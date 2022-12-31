<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class ApiDocsController extends Controller
{
    public function index(): View
    {
        return view('docs.api');
    }

    // public function download(): BinaryFileResponse
    // {
    //     return response()->download(app_path('../src/RetroAchievementsApiClient.php'));
    // }
}
