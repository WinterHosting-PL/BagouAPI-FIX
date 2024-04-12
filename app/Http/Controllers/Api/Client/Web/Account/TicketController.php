<?php

namespace App\Http\Controllers\Api\Client\Web\Account;

use App\Models\UserDiscord;
use App\Services\EncryptionService;
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
            'logs_url' => 'nullable|string',
            'message' => 'required|string|max:4000',
            'license' => 'nullable|string',
            'discord_id' => 'nullable|string',
            'discord_user_id' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,png,webp,pdf,html,zip,rar,php,ts,tsx,js,json,mkv,avi,mp4|max:8192' // Extensions autorisées et taille maximale de 2MB par fichier
        ]);
        if (!$request->logs_url) {
            $request->logs_url = '';
        }
        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            return response()->json(['status' => 'error', 'message' => $firstError], 500);
        }

        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r")) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        if ($request->discord_user_id && !$user) {
            $user = UserDiscord::where('discord_id', $request->discord_user_id)->first()->user;
        }
        if ($user) {
            if (Ticket::where('user_id', $user->id)->where('status', '!=', 'closed')->count() >= 5) {
                return response()->json(['status' => 'error', 'message' => 'You can\'t open more ticket. Please close others ticket before.'], 500);
            }
        }
        $license = 'unlicensed';
        if ($request->license) {
            $license = $request->license;
        }
        $ticket = new Ticket();
        $ticket->name = $request->subject;
        $ticket->logs_url = $request->logs_url;

        $ticket->license = $license;
        $ticket->status = 'client_answer';
        $ticket->priority = 'normal';
        if ($request->discord_id && $request->discord_user_id) {
            $ticket->discord_id = $request->discord_id;
            $ticket->discord_user_id = $request->discord_user_id;
            if ($user) {
                $ticket->user_id = $user->id;
            }
        } else {
            $ticket->user_id = $user->id;
            if ($user->discord) {
                $ticket->discord_user_id = $user->discord->discord_id;
            }
        }
        $ticket->save();

        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        if ($request->discord_id && $request->discord_user_id) {
            $message->discord_id = $request->discord_id;
            $message->discord_user_id = $request->discord_user_id;

        } else {
            $message->user_id = $user->id;
            if ($user->discord) {
                $message->discord_user_id = $user->discord->discord_id;
            }
        }

        $message->content = $request->message;
        $message->position = 1;
        $message->save();

        // Process attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $attachmentFile) {


                $attachment = new Attachment();
                if ($request->discord_id && $request->discord_user_id) {
                    $attachment->discord_id = $request->discord_id;
                    $attachment->discord_user_id = $request->discord_user_id;

                } else {
                    $attachment->user_id = $user->id;
                    if ($user->discord) {
                        $attachment->discord_user_id = $user->discord->discord_id;
                    }
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
        if ($user) {
            Mail::to($user->email)->send(new TicketCreatedMail($ticket));
        }
        Mail::to('contact@bagou450.com')->send(new TicketCreatedMail($ticket));

        return response()->json(['status' => 'success']);
    }

    public function updateTicketStatus(int $ticket, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:support_answer,client_answer,closed'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }

        $ticket = Ticket::findOrFail($ticket);
        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r")) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        if ($user) {
            if ($user->id !== $ticket->user_id && $user->role !== 1) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        } else {
            if ((!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r")) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        }
        if ($request->status === $ticket->status) {
            return response()->json(['status' => 'success']);
        }
        $ticket->status = $request->status;
        $ticket->save();
        if ($ticket->discord_id && $request->status === 'closed') {
            $discordToken = config('services.discord.token');
            $discordServer = config('services.discord.server');

            $discordEndpoint = "https://discord.com/api/v10/";
            $hearders = [
                'Authorization' => "Bot $discordToken"
            ];
            Http::withHeaders($hearders)->delete("$discordEndpoint/channels/$ticket->discord_id");
            $requestDiscordUserCreateDm = Http::withHeaders($hearders)->post($discordEndpoint . 'users/@me/channels', [
                'recipient_id' => $ticket->discord_user_id
            ])->json();
            $channelId = $requestDiscordUserCreateDm['id'];
            $message = "Dear customer,\n\n We would like to inform you that your ticket (#$ticket->id) has been successfully closed. To access the ticket logs, please create an account on our website https://bagou450.com and link it to your discord account.\n\nThank you for choosing our services! If you have any further inquiries or require assistance, please don't hesitate to reach out to us.\n\nHave a great day!\n\nSincerely,Bagouox\nBagou450 Team";
            if ($ticket->user) {
                $firstname = $ticket->user->firstname;
                $lastname = $ticket->user->lastname;
                if (!$lastname || $firstname) {
                    $lastname = '';
                    $firstname = 'customer';
                }
                $message = "Dear $lastname $firstname,\n\n We would like to inform you that your ticket (#$ticket->id) has been successfully closed. You can review the ticket logs by clicking on this link https://bagou450.com/tickets/$ticket->id .\n\nThank you for choosing our services! If you have any further inquiries or require assistance, please don't hesitate to reach out to us.\n\nHave a great day!\n\nSincerely\nBagouox\nBagou450 Team";
            }
            Http::withHeaders($hearders)->post($discordEndpoint . 'channels/' . $channelId . '/messages', [
                'content' => $message
            ]);
        }
        // Send email to user and contact
        if ($ticket->user) {
            Mail::to($ticket->user->email)->send(new TicketStatusUpdatedMail($ticket));

        }

        Mail::to('contact@bagou450.com')->send(new TicketStatusUpdatedMail($ticket));

        return response()->json(['status' => 'success']);
    }

    public function addMessage(int $ticket, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'discord_user_id' => 'nullable|string',
            'discord_id' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,png,webp,pdf,html,zip,rar,php,ts,tsx,js,json,mkv,avi,mp4|max:8192' // Extensions autorisées et taille maximale de 2MB par fichier
        ]);


        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data']);
        }
        $ticket = Ticket::findOrFail($ticket);
        $user = auth('sanctum')->user();
        if (!$user && (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r")) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user) {
            if ($user->id !== $ticket->user_id && $user->role !== 1) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        } else {
            if (!$request->discord_id && !$request->discord_user_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r") {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        }

        $message = new TicketMessage();
        $message->ticket_id = $ticket->id;
        if ($request->discord_id && $request->discord_user_id) {
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
                if ($request->discord_id && $request->discord_user_id) {
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
        if ($user) {
            if ($user->role !== 1) {
                $ticket->status = 'client_answer';
            } else {
                $ticket->status = 'support_answer';
            }
        } else {
            if ($request->bearerToken() === "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r" && $request->discord_user_id !== '444165634155085824') {
                $ticket->status = 'client_answer';
            } else {
                $ticket->status = 'support_answer';
            }
        }

        $ticket->save();
        // Send email to user and contact
        $discordToken = config('services.discord.token');
        $discordServer = config('services.discord.server');

        $hearders = [
            'Authorization' => "Bot $discordToken"
        ];
        $discordEndpoint = "https://discord.com/api/v10/";

        if ($ticket->discord_id && $request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r") {


            $requestDiscord = Http::withHeaders($hearders);
            foreach ($attachementlist as $attachment) {
                $requestDiscord->attach($attachment['name'], $attachment['content'], $attachment['name']);
            }
            $response = $requestDiscord->post($discordEndpoint . 'channels/' . $ticket->discord_id . '/messages', [
                'content' => 'New message from **' . strtoupper($user->lastname) . ' ' . ucfirst($user->firstname) . "**\n ```" . strval($request->message) . ' ```'
            ]);


        }
        if ($ticket->discord_user_id) {
            //Send pm to the user
            $requestDiscordUserCreateDm = Http::withHeaders($hearders)->post($discordEndpoint . 'users/@me/channels', [
                'recipient_id' => $ticket->discord_user_id
            ])->json();
            if ($ticket->status === 'support_answer') {
                $channelId = $requestDiscordUserCreateDm['id'];
                Http::withHeaders($hearders)->post($discordEndpoint . 'channels/' . $channelId . '/messages', [
                    'content' => "Dear Customer,\n\nWe would like to inform you that your ticket (#$ticket->id) has received a new response.\n\nThank you for choosing our services! If you have any further inquiries or require assistance, please don't hesitate to reach out to us.\n\nHave a great day!\n\nSincerely,\nBagouox\nBagou450 Team"
                ]);
            }
        }
        if ($ticket->user) {
            Mail::to($ticket->user->email)->send(new TicketMessageAddedMail($ticket, $message));

        }
        if ($ticket->status = 'client_answer') {
            Mail::to('contact@bagou450.com')->send(new TicketMessageAddedMail($ticket, $message));

        }

        // Upload ticket detail to discord
        return response()->json(['status' => 'success']);
    }

    public function getMessages(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->id !== $ticket->user_id && $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $perPage = $request->input('perPage', 10); // Nombre de messages par page, par défaut 10
        $page = $request->input('page', 1); // Numéro de la page, par défaut 1

        $pagination = $ticket->messages()
            ->with('user') // Charger les utilisateurs associés aux messages
            ->orderBy('created_at', 'asc') // Messages les plus anciens en premier
            ->paginate($perPage, ['*'], 'page', $page);

        $messages = $pagination->items();
        $formattedMessages = array();
        foreach ($messages as $message) {
            $messageUser = $message->user;

            if (!$messageUser) {
                $messageUser = UserDiscord::where('id', $message->discord_user_id)->first();
                $messageUser = User::where('user_id', $messageUser->user_id)->first();
            }
            $firstName = $messageUser ? $messageUser->firstname : 'User';
            $lastName = $messageUser ? $messageUser->lastname : 'Discord';

            $formattedMessages[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'message' => $message->content,
                'created_at' => $message->created_at,
                'discord_user_id' => $message->discord_user_id,
                'own' => $messageUser->id === $user->id,
                'role' => $messageUser->role ? $messageUser->role : 0
            ];
        }


        return response()->json([
            'status' => 'success',
            'data' => [
                'page' => $pagination->currentPage(),
                'totalPage' => $pagination->lastPage(),
                'perPage' => $perPage,
                'messages' => $formattedMessages,
            ],
        ]);
    }


    public function getTicketList(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($request->discord !== "false") {
            $apikey = config('services.discord.botkey');
            $request = Http::withToken($apikey)->get("https://bagouox.b450.eu/tickets?email=$user->email");
            if ($request->successful()) {
                $objectRequest = $request->object();
                if (!isset($objectRequest->salt) || !isset($objectRequest->data)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid ticket transcript data.'
                    ], 500);
                }

                try {
                    $encryptionService = new EncryptionService();
                    $key = $encryptionService->PBKDF2Encode($objectRequest->salt);
                    $decodedData = $encryptionService->DecryptXChaCha($objectRequest->data, $key);
                    $decodedJsonData = json_decode($decodedData);
                    return response()->json([
                        'status' => 'success',
                        'data' => $decodedJsonData
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to decode ticket transcript data.'
                    ], 500);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tickets.'
                ], $request->status());
            }

        }
        $sort = $request->sort;
        $ticketsQuery = Ticket::where('user_id', $user->id);

        if ($user->role === 1) {
            $ticketsQuery = Ticket::query();
        }
        if ($request->search && $request->search !== '') {
            $ticketsQuery->where('name', 'LIKE', "%$request->search%");
        }
        switch ($sort) {
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

        return response()->json(['status' => 'success', 'data' => $tickets]);
    }

    public function getTicketDetails(int $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        if ($user->role !== 1 && $user->id !== $ticket->user_id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $attachments = [];
        if ($ticket->attachement) {
            $attachments = $ticket->attachement->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'size' => $attachment->size,
                ];
            });
        }
        return response()->json(['status' => 'success', 'data' => ['ticket' => $ticket, 'attachments' => $attachments]]);
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

    public function downloadAttachment(int $attachmentId)
    {

        $attachment = Attachment::where('id', $attachmentId)->first();
        if (!$attachment) {
            return response()->json(['status' => 'error', 'message' => 'Can\'t find asked file.'], 401);
        }
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

    public function getLastedTicketNumber(Request $request)
    {
        if ($request->bearerToken() !== "xV5YXpmSFHCzIj5Ha5w4AjsZwD0CTWeK7UFsk2Tigy2dIMgPG8ozXvwV3OVwqqz5r") {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        return response()->json(['status' => 'success', 'data' => Ticket::latest()->value('id')], 200);
    }


    public function getDiscordTranscript(int $ticketID)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }

        $apikey = config('services.discord.botkey');
        $request = Http::withToken($apikey)->get("https://bagouox.b450.eu/ticket/$ticketID?email=$user->email");
        if ($request->successful()) {
            $objectRequest = $request->object();
            if (!isset($objectRequest->salt) || !isset($objectRequest->data)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid ticket transcript data.'
                ], 500);
            }
            //Load encryptionService and run encryptpkdf function
            try {
                $encryptionService = new EncryptionService();
                $key = $encryptionService->PBKDF2Encode($objectRequest->salt);
                $decodedData = $encryptionService->DecryptXChaCha($objectRequest->data, $key);
                $decodedJsonData = json_decode($decodedData);
                return response()->json([
                    'status' => 'success',
                    'data' => $decodedJsonData
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to decode ticket transcript data.'
                ], 500);
            }
        } else {

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get ticket transcript.'
            ], $request->status());
        }

    }

    public function sendToDiscord(int $ticketID, Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        $apikey = config('services.discord.botkey');
        $salt = "";
        $encodedData = "";
        try {
            $encryptionService = new EncryptionService();
            $salt = $encryptionService->CreateSalt();
            $key = $encryptionService->PBKDF2Encode($salt);
            $encodedData = $encryptionService->EncryptXChaCha($request->data, $key);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to encode ticket message data.',
                'error' => $e->getMessage()
            ], 500);
        }
        $request = Http::withToken($apikey)->post("https://bagouox.b450.eu/ticket/$ticketID", [
            'email' => $user->email,
            'content' => $encodedData,
            'salt' => $salt
        ]);
        if (!$request->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send ticket message to Discord.'
            ], $request->status());
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Ticket message sent to Discord successfully.'
        ]);
    }
    public function closeDiscord(int $ticketID) {
        $user = auth('sanctum')->user();
       if (!$user) {
           return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
       }
        $apikey = config('services.discord.botkey');

        $request = Http::withToken($apikey)->delete("https://bagouox.b450.eu/ticket/$ticketID", ["email" => $user->email]);
        if (!$request->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send action to Discord.'
            ], $request->status());
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Ticket was closed successfully.'
        ]);
    }
    public function downloadDiscordAttachment(int $ticketID, string $attachmentUUID) {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        $apikey = config('services.discord.botkey');

        $request = Http::withToken($apikey)->get("https://bagouox.b450.eu/ticket/$ticketID/attachment/$attachmentUUID?email=$user->email");
        if (!$request->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get attachment.'
            ], $request->status());
        }

        return response($request->body(), 200, [
            'Content-Type' => $request->header('Content-Type'),
            'Content-Disposition' => $request->header('Content-Disposition')
        ]);
    }

}
