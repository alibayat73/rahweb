<?php

namespace App\Http\Controllers;

use App\Domain\Services\TicketWorkflowService;
use App\Http\Requests\BulkApproveRequest;
use App\Http\Requests\TicketDecisionRequest;
use App\Http\Requests\TicketIndexRequest;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminTicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected TicketWorkflowService $workflowService) {}

    public function index(TicketIndexRequest $request): Response
    {
        $tickets = Ticket::query()
            ->when(! empty($filters['status']), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when(! empty($filters['search']), fn (Builder $query) => $query->whereAny(['title', 'description'], 'like', '%'.$request->string('search').'%'))
            ->with(['user', 'latestDecision', 'deliveryAttempts'])
            ->latest()
            ->paginate();

        return Inertia::render('admin/tickets/Index', [
            'tickets' => $tickets,
            'filters' => request()->only('status', 'search'),
        ]);
    }

    public function approve(TicketDecisionRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('approve', $ticket);

        DB::transaction(function () use ($ticket, $request) {
            $this->workflowService->approve($ticket, $request->string('note')->toString());
        });

        return redirect()->back()->with('success', 'Ticket approved successfully.');
    }

    public function reject(TicketDecisionRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('approve', $ticket);

        DB::transaction(function () use ($ticket, $request) {
            $this->workflowService->reject($ticket, $request->string('note')->toString());
        });

        return redirect()->back()->with('success', 'Ticket rejected.');
    }

    public function bulkApprove(BulkApproveRequest $request): RedirectResponse
    {
        $this->authorize('bulkApprove', Ticket::class);

        $tickets = Ticket::query()->findMany($request->array('ticket_ids'));

        DB::transaction(function () use ($tickets, $request) {
            foreach ($tickets as $ticket) {
                $this->workflowService->approve($ticket, $request->string('note')->toString());
            }
        });

        return redirect()->back()->with('success', 'Tickets approved successfully.');
    }
}
