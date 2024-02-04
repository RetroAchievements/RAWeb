<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DeveloperController extends Controller
{
    public function index(): View
    {
        return view('developer.index')
            ->with('');
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(User $developer): void
    {
    }

    public function edit(User $developer): void
    {
    }

    public function update(Request $request, User $developer): void
    {
    }

    public function destroy(User $developer): void
    {
    }
}
