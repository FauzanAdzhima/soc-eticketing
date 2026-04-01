<?php

namespace App\Livewire\Pages\Tickets;

use App\Events\TicketResolvedByResponder;
use App\Models\IncidentResponseAction;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Services\IncidentResponseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class TicketRespondPage extends Component
{
    public string $ticketPublicId = '';

    public string $actionType = IncidentResponseAction::TYPE_MITIGATION;

    public string $description = '';

    public function mount(Ticket $ticket): void
    {
        $this->authorize('respond', $ticket);
        $this->ticketPublicId = $ticket->public_id;
    }

    public function saveAction(IncidentResponseService $service): void
    {
        $ticket = Ticket::query()->where('public_id', $this->ticketPublicId)->firstOrFail();
        $this->authorize('recordResponseAction', $ticket);

        $this->validate([
            'actionType' => ['required', 'string', 'in:'.implode(',', IncidentResponseAction::allowedTypes())],
            'description' => ['required', 'string', 'min:5', 'max:20000'],
        ], [], [
            'actionType' => 'jenis tindakan',
            'description' => 'deskripsi',
        ]);

        $user = Auth::user();
        assert($user instanceof User);

        try {
            $service->storeAction($ticket, $user, $this->actionType, $this->description, null);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        $this->description = '';
        session()->flash('toast_success', 'Tindakan respons dicatat.');
    }

    public function startResponseHandling(): void
    {
        $ticket = Ticket::query()->where('public_id', $this->ticketPublicId)->firstOrFail();
        $this->authorize('beginResponseHandling', $ticket);

        $user = Auth::user();
        assert($user instanceof User);

        try {
            $ticket->updateSubStatus(Ticket::SUB_STATUS_RESPONSE, $user);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Fase penanganan beralih ke Response. Anda dapat mencatat tindakan.');
    }

    public function markResolved(IncidentResponseService $service): void
    {
        $ticket = Ticket::query()->where('public_id', $this->ticketPublicId)->firstOrFail();

        try {
            $service->markResponseResolved($ticket, Auth::user());
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        // Broadcast ke seluruh koordinator setelah responder menandai selesai.
        event(new TicketResolvedByResponder($ticket));

        session()->flash('toast_success', 'Penanganan ditandai selesai (Resolution).');
    }

    public function render(): View
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->ticketPublicId)
            ->with([
                'category',
                'creator',
                'organization',
                'evidences',
                'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
                'analyses' => fn ($q) => $q
                    ->orderByDesc('created_at')
                    ->with([
                        'performer',
                        'iocs' => fn ($iq) => $iq->with('iocType'),
                    ]),
                'responseActions' => fn ($q) => $q->with('performer')->orderBy('created_at'),
            ])
            ->firstOrFail();

        $this->authorize('respond', $ticket);

        /** @var User $user */
        $user = Auth::user();

        $canRecord = $user->can('recordResponseAction', $ticket);
        $canMarkResolved = $user->can('markResponseResolved', $ticket);
        $canStartResponse = $user->can('beginResponseHandling', $ticket);

        $latestAnalysis = $ticket->analyses->first();

        $timeline = $this->buildTimeline($ticket);

        return view('livewire.pages.tickets.ticket-respond-page', [
            'ticket' => $ticket,
            'latestAnalysis' => $latestAnalysis,
            'canRecord' => $canRecord,
            'canMarkResolved' => $canMarkResolved,
            'canStartResponse' => $canStartResponse,
            'phase' => $ticket->responderWorkPhase(),
            'timeline' => $timeline,
        ]);
    }

    /**
     * @return Collection<int, array{type: string, at: \Illuminate\Support\Carbon|null, payload: mixed}>
     */
    private function buildTimeline(Ticket $ticket): Collection
    {
        $entries = collect();

        foreach ($ticket->responseActions as $action) {
            $entries->push([
                'type' => 'action',
                'at' => $action->created_at,
                'payload' => $action,
            ]);
        }

        $logs = TicketLog::query()
            ->where('ticket_id', $ticket->id)
            ->whereIn('action', ['response_marked_resolved', 'sub_status_updated'])
            ->with('user')
            ->orderBy('created_at')
            ->get();

        foreach ($logs as $log) {
            if ($log->action === 'response_marked_resolved') {
                $entries->push([
                    'type' => 'log_resolved',
                    'at' => $log->created_at,
                    'payload' => $log,
                ]);

                continue;
            }

            if ($log->action === 'sub_status_updated') {
                $data = json_decode((string) $log->data, true) ?: [];
                $to = (string) ($data['to'] ?? '');
                if (in_array($to, [
                    Ticket::SUB_STATUS_RESPONSE,
                    Ticket::SUB_STATUS_RESOLUTION,
                    Ticket::SUB_STATUS_ANALYSIS,
                ], true)) {
                    $entries->push([
                        'type' => 'log_sub_status',
                        'at' => $log->created_at,
                        'payload' => $log,
                    ]);
                }
            }
        }

        return $entries->sortBy('at')->values();
    }
}
