<?php
// app/Http/Controllers/Admin/MessageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Tenant;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // ... [index() and show() methods remain exactly the same] ...
    
    public function index(Request $request)
    {
        $admin = Auth::user();

        $conversations = Conversation::with(['tenant.user', 'latestMessage'])
            ->where('deleted_by_admin', false)
            ->whereNull('deleted_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $total = $conversations->count();
        $unreadCount = $conversations->sum(fn ($c) =>
            $c->messages()
              ->whereNull('read_at')
              ->where('sender_role', 'tenant')
              ->count()
        );

        $conversationsForReact = $conversations->mapWithKeys(function ($c) {
            $latest = $c->latestMessage;
            return [
                $c->id => [
                    'id'      => $c->id,
                    'tenant'  => $c->tenant->user->name ?? 'Unknown',
                    'email'   => $c->tenant->user->email ?? '',
                    'subject' => $c->subject,
                    'last'    => $latest?->body,
                    'last_at' => $c->last_message_at?->format('M d, Y · h:i A'),
                    'unread'  => $c->messages()->whereNull('read_at')->where('sender_role', 'tenant')->count(),
                ],
            ];
        })->toArray();

        $tenants = Tenant::with('user')
            ->join('users', 'tenants.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('tenants.*')
            ->get()
            ->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->user->name,
                'email' => $t->user->email,
                'phone' => $t->user->phone,
            ])
            ->values();

        return view('admin.messages', compact(
            'admin',
            'conversations',
            'conversationsForReact',
            'tenants',
            'total',
            'unreadCount'
        ));
    }

    public function show(Conversation $conversation)
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_role', 'tenant')
            ->update(['read_at' => now()]);

        $conversation->load(['tenant.user', 'messages.sender', 'messages.reactions']);

        $visible = $conversation->messages
            ->reject(fn (Message $m) => $m->isHiddenFrom('admin'));

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $conversation->id,
                'tenant'   => $conversation->tenant->user->name ?? 'Unknown',
                'subject'  => $conversation->subject,
                'messages' => $visible->map(fn (Message $m) => [
                    'id'                   => $m->id,
                    'sender'               => $m->sender->name ?? 'Unknown',
                    'sender_id'            => $m->sender_id,
                    'role'                 => $m->sender_role,
                    'body'                 => $m->renderBody(),
                    'deleted_for_everyone' => $m->isDeletedForEveryone(),
                    'created_at'           => $m->created_at?->format('M d, Y · h:i A'),
                    'reactions'            => $m->reactionSummary(),
                ])->values(),
            ],
        ]);
    }

    /**
     * Create a new conversation + first message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'subject'   => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string'],
        ]);

        $admin  = Auth::user();
        $tenant = Tenant::with('user')->findOrFail($validated['tenant_id']);

        $conversation = Conversation::create([
            'tenant_id'       => $tenant->id,
            'admin_id'        => $admin->id,
            'subject'         => $validated['subject'],
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'sender_role'     => 'admin',
            'body'            => $validated['body'],
        ]);

        // ── TRIGGER SMS TO TENANT ──
        if ($tenant->user && $tenant->user->phone) {
            $msg = "JLM Cozy Cabin: You have received a new message from the administrator. Log in to your account and visit the Messages section to view and reply.";
            SmsService::send($tenant->user->phone, $msg);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data'    => ['conversation_id' => $conversation->id],
        ]);
    }

    /**
     * Append a reply from the admin.
     */
    public function reply(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $admin = Auth::user();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'sender_role'     => 'admin',
            'body'            => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // ── TRIGGER SMS TO TENANT ──
        $conversation->load('tenant.user');
        if ($conversation->tenant?->user?->phone) {
            $msg = "JLM Cozy Cabin: You have received a new message from the administrator. Log in to your account and visit the Messages section to view and reply.";
            SmsService::send($conversation->tenant->user->phone, $msg);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
        ]);
    }

    // ... [deleteMessage(), reactToMessage(), deleteConversation() remain exactly the same] ...
    
    public function deleteMessage(Message $message)
    {
        $message->update(['deleted_by_receiver' => true]);

        $fresh = $message->fresh();
        if ($fresh->deleted_by_sender && $fresh->deleted_by_receiver) {
            $fresh->update([
                'deleted_at'              => now(),
                'deleted_for_everyone_at' => now(),
                'body'                    => 'This message was deleted.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message deleted.',
        ]);
    }

    public function reactToMessage(Request $request, Message $message)
    {
        $validated = $request->validate([
            'reaction' => ['required', 'string', 'max:16'],
        ]);

        $userId = Auth::id();

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing && $existing->reaction === $validated['reaction']) {
            $existing->delete();
            $action = 'removed';
        } elseif ($existing) {
            $existing->update(['reaction' => $validated['reaction']]);
            $action = 'updated';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => $userId,
                'reaction'   => $validated['reaction'],
            ]);
            $action = 'added';
        }

        $message->load('reactions');

        return response()->json([
            'success'   => true,
            'action'    => $action,
            'reactions' => $message->reactionSummary(),
        ]);
    }

    public function deleteConversation(Conversation $conversation)
    {
        $conversation->update(['deleted_by_admin' => true]);

        $fresh = $conversation->fresh();
        if ($fresh->deleted_by_admin && $fresh->deleted_by_tenant) {
            $fresh->update(['deleted_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted.',
        ]);
    }
}