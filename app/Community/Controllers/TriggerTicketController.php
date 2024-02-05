<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use App\Models\Achievement;
use App\Models\TriggerTicket;
use App\Support\Concerns\HandlesResources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TriggerTicketController extends Controller
{
    use HandlesResources;

    public function resourceName(): string
    {
        return 'trigger.ticket';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function create(?Achievement $achievement = null): View
    {
        $this->authorize('create', TriggerTicket::class);

        if ($achievement) {
            $_GET['i'] = $achievement->id;
        }

        return view('ticket.create')->with('achievement', $achievement);
    }

    public function store(Request $request): void
    {
    }

    public function show(TriggerTicket $ticket): View
    {
        return view('ticket.show')->with('ticket', $ticket);
    }

    public function edit(TriggerTicket $ticket): void
    {
    }

    public function update(Request $request, TriggerTicket $ticket): void
    {
    }

    public function destroy(TriggerTicket $ticket): void
    {
    }
}
