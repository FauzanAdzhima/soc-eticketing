<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Exception;

class TicketController extends Controller
{
    public function index()
    {
        return Ticket::latest()->get();
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'title' => 'required|string|max:255',
        //     'description' => 'required|string',
        //     'severity' => 'nullable|string|max:50',
        // ]);

        $ticket = Ticket::create([
            'public_id' => Str::uuid(),
            'title' => $request->title,
            'description' => $request->description,
            // 'severity' => $request->severity,
            // 'created_by' => auth()->id(),
        ]);

        return response()->json($ticket, 201);

        // dd($request->all());
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

        // 🔐 1. Validasi login
        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated'
            ], 401);
        }

        // 🔐 2. Validasi role (flexible)
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

        // 🔐 3. Validasi user tujuan
        $targetUser = User::findOrFail($request->user_id);

        if (!$targetUser->hasAnyRole(['analis', 'responder', 'pic'])) {
            return response()->json([
                'error' => 'User is not valid for assignment'
            ], 400);
        }

        // 🔐 4. Validasi ticket status
        if ($ticket->status === 'closed') {
            return response()->json([
                'error' => 'Cannot assign closed ticket'
            ], 400);
        }

        // 🔐 5. Optional: hindari assign ke user yang sama
        $alreadyAssigned = $ticket->assignments()
            ->where('user_id', $targetUser->id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'error' => 'User already assigned'
            ], 400);
        }

        // 🚀 6. Jalankan assign
        $ticket->assignTo($targetUser->id, $user);

        return response()->json([
            'message' => 'Ticket assigned successfully'
        ]);
    }
}
