<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        return response()->json(Ticket::latest()->get());
    }

    public function storePublic(Request $request, TicketService $ticketService): JsonResponse
    {
        return $this->storeTicket($request, $ticketService, null);
    }

    public function storeAuthenticated(Request $request, TicketService $ticketService): JsonResponse
    {
        return $this->storeTicket($request, $ticketService, $request->user()?->id);
    }

    private function storeTicket(Request $request, TicketService $ticketService, ?int $createdBy): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_email' => ['required', 'email', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:30'],
            'reporter_organization_id' => ['nullable', 'exists:organizations,id'],
            'reporter_organization_name' => ['nullable', 'string', 'max:255'],
            'incident_category_id' => ['required', 'exists:incident_categories,id'],
            'incident_severity' => ['nullable', 'in:Low,Medium,High,Critical'],
            'incident_description' => ['required', 'string'],
            'incident_time' => ['required', 'date'],
            'evidence_files' => ['nullable', 'array'],
            'evidence_files.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasOrgId = filled($request->input('reporter_organization_id'));
            $hasOrgName = filled($request->input('reporter_organization_name'));

            if ($hasOrgId === $hasOrgName) {
                $validator->errors()->add(
                    'reporter_organization_id',
                    'Pilih salah satu: organization ID (ASN/pegawai) atau organization name manual.'
                );
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $payload['created_by'] = $createdBy;
        $payload['evidence_files'] = $request->file('evidence_files', []);

        $ticket = $ticketService->createTicket($payload);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => $ticket,
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();

        $value = $request->input('sub_status') ?? $request->input('status');
        if ($value === null || $value === '') {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'sub_status' => ['Kirim sub_status atau status.'],
                ],
            ], 422);
        }

        if (is_string($value) && strcasecmp($value, Ticket::STATUS_CLOSED) === 0) {
            $this->authorize('close', $ticket);
            try {
                $ticket->close($user);

                return response()->json([
                    'message' => 'Ticket closed',
                    'data' => $ticket->fresh(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                ], 400);
            }
        }

        $this->authorize('updateStatus', $ticket);

        try {
            $ticket->updateStatus($value, $user);

            return response()->json([
                'message' => 'Status updated',
                'data' => $ticket->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function verifyReport(Request $request, int $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('verifyReport', $ticket);

        try {
            $ticket->verifyReport($request->user());

            return response()->json([
                'message' => 'Report verified',
                'data' => $ticket->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejectReport(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:15', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::findOrFail($id);
        $this->authorize('rejectReport', $ticket);

        try {
            $ticket->rejectReport($request->user(), trim((string) $request->input('reason')));

            return response()->json([
                'message' => 'Report rejected',
                'data' => $ticket->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'kind' => ['nullable', 'in:'.TicketAssignment::KIND_ASSIGNED_PRIMARY.','.TicketAssignment::KIND_CONTRIBUTOR],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::findOrFail($id);
        $this->authorize('assign', $ticket);
        $user = $request->user();

        $targetUser = User::findOrFail($request->integer('user_id'));
        $kind = $request->input('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY);

        if (! $targetUser->hasAnyRole(['analis', 'responder', 'pic'])) {
            return response()->json([
                'error' => 'User is not valid for assignment',
            ], 400);
        }

        $alreadyAssigned = $ticket->assignments()
            ->where('user_id', $targetUser->id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'error' => 'User already assigned',
            ], 400);
        }

        if ($kind === TicketAssignment::KIND_CONTRIBUTOR) {
            $ticket->addContributor($targetUser->id, $user);
        } else {
            $ticket->assignTo($targetUser->id, $user);
        }

        $ticket->refresh();

        return response()->json([
            'message' => 'Ticket assigned successfully',
            'data' => [
                'report_status' => $ticket->report_status,
                'status' => $ticket->status,
                'sub_status' => $ticket->sub_status,
            ],
        ]);
    }
}
