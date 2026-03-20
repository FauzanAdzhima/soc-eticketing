<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
}
