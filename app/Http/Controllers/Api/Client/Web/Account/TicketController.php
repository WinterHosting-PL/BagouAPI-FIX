<?php

namespace App\Http\Controllers\Api\Client\Web\Account;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Mail\TicketMessageAddedMail;
use Illuminate\Support\Facades\Config;

class TicketController extends Controller
{
    public function createTicket(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:64',
            'logs_url' => 'required|string',
            'message' => 'required|string|max:4000',
            'license' => 'required|string',
            'discord_id' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,png,webp,pdf,html,zip,rar,php,ts,tsx,js,json,mkv,avi,mp4|max:2048' // Extensions autorisées et taille maximale de 2MB par fichier
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r"))  {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        $ticket = new Ticket();
        $ticket->name = $request->subject;
        $ticket->logs_url = $request->logs_url;
        $ticket->license = $request->license;
        $ticket->status = 'client_answer';
        $ticket->priority = 'normal';
        if($request->discord_id && $request->discord_user_id) {
            $ticket->discord_id = $request->discord_id;
            $ticket->discord_user_id = $request->discord_user_id;

        } else {
            $ticket->user_id = $user->id;
        }
        $ticket->save();

        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        if($request->discord_id && $request->discord_user_id) {
            $message->discord_id = $request->discord_id;
            $message->discord_user_id = $request->discord_user_id;

        } else {
            $message->user_id = $user->id;
        }
        $message->content = $request->message;
        $message->position = 1;
        $message->save();

        // Process attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $attachmentFile) {
                $attachment = new Attachment();
                if($request->discord_id && $request->discord_user_id) {
                    $attachment->discord_id = $request->discord_id;
                    $attachment->discord_user_id = $request->discord_user_id;

                } else {
                    $attachment->user_id = $user->id;
                }
                $attachment->ticket_id = $ticket->id;
                $attachment->name = $attachmentFile->getClientOriginalName();
                $attachment->size = $attachmentFile->getSize();
                $attachment->unique_name = Str::random(40); // Generate a unique name for the attachment
                $attachment->save();

                $attachmentFile->move(public_path('attachments'), $attachment->unique_name);
            }
        }
        // Send email to user and contact
        Mail::to($user->email)->send(new TicketCreatedMail($ticket));
        Mail::to('contact@bagou450.com')->send(new TicketCreatedMail($ticket));
        Mail::to('test-fb71c2@test.mailgenius.com')->send(new TicketCreatedMail($ticket));
       // Mail::to('test-e5436b@test.mailgenius.com')->send(new TicketCreatedMail($ticket));

        return response()->json(['status' => 'success']);
    }

    public function updateTicketStatus(Int $ticket, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:support_answer,client_answer,closed'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $ticket = Ticket::findOrFail($ticket);
        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" )) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->id !== $ticket->user_id && $user->role !== 1 && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" )) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $ticket->status = $request->status;
        $ticket->save();
        // Send email to user and contact
        Mail::to($ticket->user->email)->send(new TicketStatusUpdatedMail($ticket));
        Mail::to('contact@bagou450.com')->send(new TicketStatusUpdatedMail($ticket));

        return response()->json(['status' => 'success']);
    }

    public function addMessage(Int $ticket, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,png,webp,pdf,html,zip,rar,php,ts,tsx,js,json,mkv,avi,mp4|max:2048' // Extensions autorisées et taille maximale de 2MB par fichier
        ]);


        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }
        $ticket = Ticket::findOrFail($ticket);
        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" )) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->id !== $ticket->user_id && $user->role !== 1 && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" )) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        if($request->discord_id && $request->discord_user_id) {
            $message->discord_id = $request->discord_id;
            $message->discord_user_id = $request->discord_user_id;

        } else {
            $message->user_id = $user->id;
        }
        $message->content = $request->message;
        $message->position = $ticket->messages()->count() + 1;
        $message->save();

        // Process attachments
        $attachementlist = array();
        if ($request->file()) {
            foreach ($request->file() as $attachmentFile) {
                $attachment = new Attachment();
                if($request->discord_id && $request->discord_user_id) {
                    $attachment->discord_id = $request->discord_id;
                    $attachment->discord_user_id = $request->discord_user_id;

                } else {
                    $attachment->user_id = $user->id;
                }
                $attachment->ticket_id = $ticket->id;
                $attachment->name = $attachmentFile->getClientOriginalName();
                $attachment->size = $attachmentFile->getSize();
                $attachment->unique_name = Str::random(40); // Generate a unique name for the attachment
                $attachment->save();
                $attachementlist[] = array(
                    'name' => $attachmentFile->getClientOriginalName(),
                    'content' => $attachmentFile->getContent()
                );
                $attachmentFile->move(public_path('attachments'), $attachment->unique_name);
            }
        }
        if($user->role !== 1 || $request->bearerToken() === "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" ) {
            $ticket->status = 'client_answer';
        } else {
            $ticket->status = 'support_answer';
        }
        $ticket->save();
        // Send email to user and contact


        if($ticket->discord_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r") {
            $discordToken = config('services.discord.token');
            $discordServer = config('services.discord.server');

            $discordEndpoint = "https://discord.com/api/v10/";
            $hearders = [
                'Authorization' => "Bot $discordToken"
            ];
            $requestDiscord = Http::withHeaders($hearders);
            foreach($attachementlist as $attachment) {
                $requestDiscord->attach($attachment['name'], $attachment['content'], $attachment['name']);
            }
            $response = $requestDiscord->post($discordEndpoint . 'channels/' . $ticket->discord_id . '/messages', [
                'content' => 'New message from **' . strtoupper($user->lastname) . ' ' . ucfirst($user->firstname) . "**\n ```" . strval($request->message) . ' ```'
            ]);
            //Send pm to the user
            $requestDiscordUserCreateDm = Http::withHeaders($hearders)->post($discordEndpoint . 'users/@me/channels', [
                'recipient_id' => $ticket->discord_user_id
            ])->json();
            $channelId = $requestDiscordUserCreateDm['id'];
            Http::withHeaders($hearders)->post($discordEndpoint . 'channels/' . $channelId . '/messages', [
                'content' => 'New message from **' . strtoupper($user->lastname) . ' ' . ucfirst($user->firstname) . "**\nPlease go on the discord https://discord.com/channels/$discordServer/$ticket->discord_id for see it!"
            ]);

        } else {
            Mail::to($ticket->user->email)->send(new TicketMessageAddedMail($ticket, $message));
        }
        Mail::to('contact@bagou450.com')->send(new TicketMessageAddedMail($ticket, $message));

        // Upload ticket detail to discord
        return response()->json(['status' => 'success']);
    }

    public function getMessages($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->id !== $ticket->user_id && $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $messages = $ticket->messages->map(function ($message) {
            $messageuser = User::findOrFail($message->user_id);
            return [
                'position' => $message->position,
                'user' => $messageuser->firstname . ' ' . $messageuser->lastname,
                'message' => $message->content,
            ];
        });

        return response()->json(['messages' => $messages]);
    }

    public function getTicketList(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        $sort = $request->sort;
        $ticketsQuery = Ticket::where('user_id', $user->id);

        if ($user->role === 1) {
            $ticketsQuery = Ticket::query();
        }

        switch ($sort) {
            case 'status':
                $ticketsQuery->orderByRaw("FIELD(status, 'support_answer', 'client_answer', 'closed')");
                break;
            case 'asc_modified':
                $ticketsQuery->orderBy('updated_at', 'asc');
                break;
            case 'desc_modified':
                $ticketsQuery->orderBy('updated_at', 'desc');
                break;
            case 'asc_created':
                $ticketsQuery->orderBy('created_at', 'asc');
                break;
            case 'desc_created':
                $ticketsQuery->orderBy('created_at', 'desc');
                break;
            default:
                $ticketsQuery->orderByRaw("FIELD(status, 'support_answer', 'client_answer', 'closed')");
                break;
        }

        $tickets = $ticketsQuery->paginate(15, ['*'], 'page', $request->page); // 15 tickets per page, adjust as needed

        return response()->json(['tickets' => $tickets]);
    }

    public function getTicketDetails(Int $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->role !== 1 && $user->id !== $ticket->user_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $messages = $ticket->messages()->with('user')->get();
        $attachments = [];
        if($ticket->attachement) {
            $attachments = $ticket->attachement->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'size' => $attachment->size,
                ];
            });
        }


        return response()->json(['ticket' => $ticket, 'messages' => $messages, 'attachments' => $attachments]);
    }

    public function assignTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'assignee_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $ticket = Ticket::findOrFail($request->ticket_id);
        $assignee = User::findOrFail($request->assignee_id);
        $ticket->assignee_id = $assignee->id;
        $ticket->save();

        return response()->json(['status' => 'success']);
    }

    public function filterTickets(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = Ticket::query();
        if ($user->role !== 1) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->when($status, function ($query, $status) {
            return $query->where('status', $status);
        })
            ->when($priority, function ($query, $priority) {
                return $query->where('priority', $priority);
            })
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function searchTickets(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        if ($user->role === 1) {
            // Admin user, return all tickets
            $tickets = Ticket::query();
        } else {
            $tickets = Ticket::where('user_id', $user->id);
        }

        $keyword = $request->input('keyword');

        $tickets = $tickets->where(function ($query) use ($keyword) {
            $query->where('subject', 'like', '%' . $keyword . '%')
                ->orWhere('content', 'like', '%' . $keyword . '%');
        })
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function downloadAttachment($attachmentId)
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $ticket = $attachment->ticket;
        $user = auth('sanctum')->user();
        if (!$user || ($user->id !== $ticket->user_id && $user->role !== 1)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $filePath = public_path('attachments/' . $attachment->unique_name);
        if (file_exists($filePath)) {
            return response()->download($filePath, $attachment->name);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Attachment not found'], 404);
        }
    }
}
