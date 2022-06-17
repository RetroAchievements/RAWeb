<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiDocsController extends \App\Http\Controller
{
    public function index(): View
    {
        return view('docs.api');
    }

    public function download(): BinaryFileResponse
    {
        return response()->download(app_path('../src/RetroAchievementsApiClient.php'));
    }
}
