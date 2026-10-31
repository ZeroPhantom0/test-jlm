<?php
// app/Http/Controllers/Tenant/TenantMessageController.php

namespace App\Http\Controllers\Tenant;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantMessageController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return view('tenants.messages', [
                'tenant'                => null,
                'conversations'         => collect(),
                'conversationsForReact' => [],
                'total'                 => 0,
                'unreadCount'           => 0,
            ]);
        }

        $conversations = Conversation::with(['tenant.user', 'latestMessage'])
            ->where('tenant_id', $tenant->id)
            ->where('deleted_by_tenant', false)
            ->whereNull('deleted_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $total       = $conversations->count();
        $unreadCount = $conversations->sum(
            fn($c) =>
            $c->messages()
                ->whereNull('read_at')
                ->where('sender_role', 'admin')
                ->count()
        );

        $conversationsForReact = $conversations->mapWithKeys(function ($c) {
            $latest = $c->latestMessage;
            return [
                $c->id => [
                    'id'      => $c->id,
                    'subject' => $c->subject,
                    'last'    => $latest?->body,
                    'last_at' => $c->last_message_at?->format('M d, Y · h:i A'),
                    'unread'  => $c->messages()->whereNull('read_at')->where('sender_role', 'admin')->count(),
                ],
            ];
        })->toArray();

        return view('tenants.messages', compact(
            'tenant',
            'conversations',
            'conversationsForReact',
            'total',
            'unreadCount'
        ));
    }

    public function show(Conversation $conversation)
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $conversation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_role', 'admin')
            ->update(['read_at' => now()]);

        $conversation->load(['messages.sender', 'messages.reactions']);

        $visible = $conversation->messages
            ->reject(fn(Message $m) => $m->isHiddenFrom('tenant'));

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $conversation->id,
                'subject'  => $conversation->subject,
                'messages' => $visible->map(fn(Message $m) => [
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'You are not yet registered as a tenant.',
            ], 422);
        }

        $conversation = Conversation::create([
            'tenant_id'       => $tenant->id,
            'admin_id'        => null,
            'subject'         => $validated['subject'],
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_role'     => 'tenant',
            'body'            => $validated['body'],
        ]);

        $admin = User::where('role', 'admin')->first();
        if ($admin && $admin->phone) {
            $msg = "New Message from Tenant: You have received a new message from a tenant. Log in to JLM Cozy Cabin to view it.";
            SmsService::send($admin->phone, $msg);
        }

        // 🔴 Broadcast to admin screens and the specific tenant
        $messageText = $validated['body'];
        $senderName = $user->name ?? 'Tenant';

        event(new MessageSent(
            $conversation->id,
            $tenant->id,
            'tenant',
            $senderName,
            $messageText
        ));
        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data'    => ['conversation_id' => $conversation->id],
        ]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $conversation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'sender_role'     => 'tenant',
            'body'            => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        $admin = User::where('role', 'admin')->first();
        if ($admin && $admin->phone) {
            $msg = "New Message from Tenant: You have received a new message from a tenant. Log in to JLM Cozy Cabin to view it.";
            SmsService::send($admin->phone, $msg);
        }

        // 🔴 Broadcast to admin screens and the specific tenant
        $messageText = $validated['body'];
        $senderName = $user->name ?? 'Tenant';

        event(new MessageSent(
            $conversation->id,
            $tenant->id,
            'tenant',
            $senderName,
            $messageText
        ));

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
        ]);
    }

    public function deleteMessage(Message $message)
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $message->conversation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $message->update(['deleted_by_sender' => true]);

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
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $message->conversation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reaction' => ['required', 'string', 'max:16'],
        ]);

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
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
                'user_id'    => $user->id,
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
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant || $conversation->tenant_id !== $tenant->id) {
            abort(403);
        }

        $conversation->update(['deleted_by_tenant' => true]);

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
