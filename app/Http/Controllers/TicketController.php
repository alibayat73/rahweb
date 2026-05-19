<?php

namespace App\Http\Controllers;

use App\Domain\Services\TicketWorkflowService;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\TicketIndexRequest;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected TicketWorkflowService $ticketWorkflowService) {}

    public function index(TicketIndexRequest $request): Response
    {
        $tickets = Ticket::query()
            ->where('user_id', auth()->id())
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn (Builder $query): Builder => $query->whereAny(['title', 'description'], 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate();

        return Inertia::render('tickets/Index', [
            'tickets' => $tickets,
            'filters' => request()->only('status', 'search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tickets/Create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketWorkflowService->submit(
            user: $request->user(),
            title: $request->string('title')->toString(),
            description: $request->string('description')->toString(),
            attachment: $request->file('attachment'),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket submitted successfully.');
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['user']);

        return Inertia::render('tickets/Show', [
            'ticket' => $ticket,
            'attachment_url' => Storage::disk('public')->url($ticket->attachment_path),
        ]);
    }
}
