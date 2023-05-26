<?php

namespace App\Http\Controllers\Api\Client\Web\Account;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;
use App\Models\TicketMessage;

class TicketController extends Controller
{
    public function createTicket(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'logs_url' => 'nullable|string',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        $ticket = new Ticket();
        $ticket->subject = $request->subject;
        $ticket->logs_url = $request->logs_url;
        $ticket->user_id = $user->id;
        $ticket->license = $request->license;
        $ticket->status = 'client_answer';
        $ticket->save();

        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        $message->user_id = $user->id;
        $message->content = $request->message;
        $message->position = 1;
        $message->save();

        return response()->json(['status' => 'success']);
    }
    public function updateTicketStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'status' => 'required|string|in:support_answer,client_answer,closed'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $ticket = Ticket::find($request->ticket_id);
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if (!in_array($user->id, $ticket->participants)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $ticket->status = $request->status;
        $ticket->save();

        return response()->json(['status' => 'success']);
    }
    public function addMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }
        $ticket = Ticket::find($request->ticket_id);
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if (!in_array($user->id, $ticket->participants)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        $message->user_id = $user->id;
        $message->content = $request->message;
        $message->position = $ticket->messages()->count() + 1;
        $message->save();

        return response()->json(['status' => 'success']);
    }
    public function getMessages($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if (!in_array($user->id, $ticket->participants)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $messages = $ticket->messages->map(function ($message) {
            return [
                'position' => $message->position,
                'user' => $message->user->first_name . ' ' . $message->user->last_name,
                'message' => $message->content,
            ];
        });

        return response()->json(['messages' => $messages]);
    }
}