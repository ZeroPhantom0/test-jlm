<?php
// app/Http/Controllers/Admin/MessageController.php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\SmsService;

class MessageController extends Controller
{
    public function index()
    {
        cache()->put('admin_online_' . Auth::id(), true, now()->addMinutes(30));

        $conversations = Conversation::with(['tenant.user', 'latestMessage'])
            ->where('deleted_by_admin', false)
            ->whereNull('deleted_at')
            ->orderByDesc('last_message_at')
            ->get();

        $unreadCount = $conversations->sum(
            fn ($c) => $c->messages()
                ->whereNull('read_at')
                ->where('sender_role', 'tenant')
                ->count()
        );

        $tenants = User::where('role', 'tenant')
            ->with('tenant')
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $tenant = $u->tenant ?: Tenant::ensureForUser($u);

                return [
                    'id'    => $tenant->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone ?? '',
                ];
            })
            ->values();

        return view('admin.messages', [
            'conversations' => $conversations,
            'tenants'       => $tenants,
            'total'         => $conversations->count(),
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        cache()->put('admin_online_' . Auth::id(), true, now()->addMinutes(30));

        $conversation = Conversation::with(['tenant.user', 'messages.sender', 'messages.reactions'])
            ->where('deleted_by_admin', false)
            ->findOrFail($id);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_role', 'tenant')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => $this->serializeMessage($m))
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $conversation->id,
                'tenant'   => $conversation->tenant->user->name ?? 'Unknown',
                'subject'  => $conversation->subject,
                'messages' => $messages,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'     => ['required', 'integer', 'exists:tenants,id'],
            'subject'       => ['required', 'string', 'max:255'],
            'body'          => ['nullable', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if (empty(trim((string) ($validated['body'] ?? ''))) && empty($validated['attachments'])) {
            return response()->json(['message' => 'Message body or an attachment is required.'], 422);
        }

        $tenant = Tenant::with('user')->findOrFail($validated['tenant_id']);

        $conversation = Conversation::findOrCreateForTenant(
            $tenant,
            Auth::id(),
            $validated['subject']
        );

        if ($conversation->deleted_by_admin) {
            $conversation->update(['deleted_by_admin' => false, 'deleted_at' => null]);
        }

        $attachments = $this->saveAttachments($request, $conversation->id);

        $message = $conversation->messages()->create([
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => $validated['body'] ?? '',
            'attachments' => $attachments,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->broadcast($conversation, $message, $tenant);

        // ==========================================
        // SMS LOGIC
        // ==========================================
        if (!cache()->has('user_online_' . $tenant->user_id) && $tenant->user->phone) {
            SmsService::send(
                $tenant->user->phone,
                "You have a new message from the Admin. Please log in to your account to read it."
            );
        }
        // ==========================================

        return response()->json([
            'success' => true,
            'data'    => [
                'conversation_id' => $conversation->id,
                'message_id'      => $message->id,
            ],
        ]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'body'          => ['nullable', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if (empty(trim((string) ($validated['body'] ?? ''))) && empty($validated['attachments'])) {
            return response()->json(['message' => 'Message body or an attachment is required.'], 422);
        }

        $conversation = Conversation::with('tenant.user')
            ->where('deleted_by_admin', false)
            ->findOrFail($id);

        $attachments = $this->saveAttachments($request, $conversation->id);

        $message = $conversation->messages()->create([
            'sender_id'   => Auth::id(),
            'sender_role' => 'admin',
            'body'        => $validated['body'] ?? '',
            'attachments' => $attachments,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->broadcast($conversation, $message, $conversation->tenant);

        // ==========================================
        // SMS LOGIC
        // ==========================================
        $tenantUser = $conversation->tenant->user;
        if ($tenantUser && !cache()->has('user_online_' . $tenantUser->id) && $tenantUser->phone) {
            SmsService::send(
                $tenantUser->phone,
                "You have a new message from the Admin. Please log in to your account to read it."
            );
        }
        // ==========================================

        return response()->json([
            'success' => true,
            'data'    => $this->serializeMessage($message),
        ]);
    }

    public function destroyMessage(int $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);

        $message->update([
            'deleted_by_receiver'     => true,
            'deleted_for_everyone_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyConversation(int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->update([
            'deleted_by_admin' => true,
            'deleted_at'       => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function react(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'reaction' => ['required', 'string', 'max:16'],
        ]);

        $message = Message::findOrFail($messageId);

        $existing = $message->reactions()
            ->where('user_id', Auth::id())
            ->where('reaction', $validated['reaction'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $message->reactions()->where('user_id', Auth::id())->delete();
            $message->reactions()->create([
                'user_id'  => Auth::id(),
                'reaction' => $validated['reaction'],
            ]);
        }

        return response()->json([
            'success'   => true,
            'reactions' => $message->fresh()->reactionSummary(),
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function saveAttachments(Request $request, int $conversationId): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $saved = [];
        $dir   = 'messages/' . $conversationId;

        foreach ($request->file('attachments') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $ext  = $file->getClientOriginalExtension() ?: 'bin';
            $name = Str::random(24) . '.' . $ext;
            $path = $file->storeAs($dir, $name, 'public');

            $saved[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ];
        }

        return $saved;
    }

    private function serializeMessage(Message $m): array
    {
        return [
            'id'                   => $m->id,
            'sender'               => $m->sender->name ?? ($m->sender_role === 'admin' ? 'Admin' : 'Tenant'),
            'role'                 => $m->sender_role,
            'body'                 => $m->body,
            'attachments'          => $m->attachmentsForClient(),
            'created_at'           => $m->created_at?->format('M d, Y g:i A'),
            'deleted_for_everyone' => $m->isDeletedForEveryone(),
            'reactions'            => $m->reactionSummary(),
        ];
    }

    private function broadcast(Conversation $conversation, Message $message, ?Tenant $tenant): void
    {
        if (! $tenant) {
            return;
        }

        event(new MessageSent(
            conversationId: $conversation->id,
            tenantId:       $tenant->id,
            userId:         $tenant->user_id,
            senderRole:     'admin',
            senderName:     Auth::user()->name ?? 'Admin',
            body:           $message->body,
            messageId:      $message->id,
        ));
    }
}