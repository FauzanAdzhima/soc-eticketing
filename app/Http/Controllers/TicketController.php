<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function index()
    {
        return Ticket::latest()->get();
    }

    public function store(Request $request, TicketService $ticketService)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('pic')) {
            return response()->json([
                'message' => 'Hanya role PIC yang dapat membuat tiket melalui akun.'
            ], 403);
        }

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
        $payload['created_by'] = $user?->id;
        $payload['evidence_files'] = $request->file('evidence_files', []);

        $ticket = $ticketService->createTicket($payload);

        return response()->json([
            'message' => 'Ticket created successfully',
            'data' => $ticket,
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $newStatus = $request->status;
        // $user = Auth::user();
        $user = $request->user();

        try {
            $ticket->updateStatus($newStatus, $user);

            return response()->json([
                'message' => 'Status updated',
                'data' => $ticket
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function assign(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();

        // 1. Validasi login
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // 2. Validasi role (flexible)
        $isAssigned = $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (
            !$user->hasAnyRole(['pic', 'analis', 'responder'])
            && !$isAssigned
        ) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        // 3. Validasi user tujuan
        $targetUser = User::findOrFail($request->user_id);

        if (!$targetUser->hasAnyRole(['analis', 'responder', 'pic'])) {
            return response()->json([
                'error' => 'User is not valid for assignment'
            ], 400);
        }

        // 4. Validasi ticket status
        if ($ticket->status === 'closed') {
            return response()->json([
                'error' => 'Cannot assign closed ticket'
            ], 400);
        }

        // 5. Optional: hindari assign ke user yang sama
        $alreadyAssigned = $ticket->assignments()
            ->where('user_id', $targetUser->id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'error' => 'User already assigned'
            ], 400);
        }

        // 6. Jalankan assign
        $ticket->assignTo($targetUser->id, $user);

        return response()->json([
            'message' => 'Ticket assigned successfully'
        ]);
    }
}
