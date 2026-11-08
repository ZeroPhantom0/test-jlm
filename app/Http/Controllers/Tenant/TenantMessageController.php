<?php
// app/Http/Controllers/Tenant/TenantMessageController.php

namespace App\Http\Controllers\Tenant;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenantMessageController extends Controller
{
    public function index()
    {
        $tenant = Tenant::ensureForCurrentUser();

        $conversations = Conversation::with(['latestMessage'])
            ->where('tenant_id', $tenant->id)
            ->where('deleted_by_tenant', false)
            ->whereNull('deleted_at')
            ->orderByDesc('last_message_at')
            ->get();

        $unreadCount = $conversations->sum(
            fn ($c) => $c->messages()
                ->whereNull('read_at')
                ->where('sender_role', 'admin')
                ->count()
        );

        return view('tenants.messages', [
            'conversations' => $conversations,
            'total'         => $conversations->count(),
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::ensureForCurrentUser();

        $conversation = Conversation::with(['messages.sender', 'messages.reactions'])
            ->where('tenant_id', $tenant->id)
            ->where('deleted_by_tenant', false)
            ->findOrFail($id);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_role', 'admin')
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
                'subject'  => $conversation->subject,
                'messages' => $messages,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject'       => ['required', 'string', 'max:255'],
            'body'          => ['nullable', 'string'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if (empty(trim((string) ($validated['body'] ?? ''))) && empty($validated['attachments'])) {
            return response()->json(['message' => 'Message body or an attachment is required.'], 422);
        }

        $tenant = Tenant::ensureForCurrentUser();

        $conversation = Conversation::findOrCreateForTenant(
            $tenant,
            null,
            $validated['subject']
        );

        if ($conversation->deleted_by_tenant) {
            $conversation->update(['deleted_by_tenant' => false, 'deleted_at' => null]);
        }

        $attachments = $this->saveAttachments($request, $conversation->id);

        $message = $conversation->messages()->create([
            'sender_id'   => Auth::id(),
            'sender_role' => 'tenant',
            'body'        => $validated['body'] ?? '',
            'attachments' => $attachments,
        ]);

        $conversation->update(['last_message_at' => now()]);

        event(new MessageSent(
            conversationId: $conversation->id,
            tenantId:       $tenant->id,
            userId:         $tenant->user_id,
            senderRole:     'tenant',
            senderName:     Auth::user()->name ?? 'Tenant',
            body:           $message->body,
            messageId:      $message->id,
        ));

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

        $tenant = Tenant::ensureForCurrentUser();

        $conversation = Conversation::where('tenant_id', $tenant->id)
            ->where('deleted_by_tenant', false)
            ->findOrFail($id);

        $attachments = $this->saveAttachments($request, $conversation->id);

        $message = $conversation->messages()->create([
            'sender_id'   => Auth::id(),
            'sender_role' => 'tenant',
            'body'        => $validated['body'] ?? '',
            'attachments' => $attachments,
        ]);

        $conversation->update(['last_message_at' => now()]);

        event(new MessageSent(
            conversationId: $conversation->id,
            tenantId:       $tenant->id,
            userId:         $tenant->user_id,
            senderRole:     'tenant',
            senderName:     Auth::user()->name ?? 'Tenant',
            body:           $message->body,
            messageId:      $message->id,
        ));

        return response()->json([
            'success' => true,
            'data'    => $this->serializeMessage($message),
        ]);
    }

    public function destroyMessage(int $messageId): JsonResponse
    {
        $tenant  = Tenant::ensureForCurrentUser();
        $message = Message::whereHas(
            'conversation',
            fn ($q) => $q->where('tenant_id', $tenant->id)
        )->findOrFail($messageId);

        $message->update([
            'deleted_by_sender'       => true,
            'deleted_for_everyone_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyConversation(int $id): JsonResponse
    {
        $tenant = Tenant::ensureForCurrentUser();

        $conversation = Conversation::where('tenant_id', $tenant->id)
            ->findOrFail($id);

        $conversation->update([
            'deleted_by_tenant' => true,
            'deleted_at'        => now(),
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
            'sender'               => $m->sender->name ?? ($m->sender_role === 'admin' ? 'Admin' : 'You'),
            'role'                 => $m->sender_role,
            'body'                 => $m->body,
            'attachments'          => $m->attachmentsForClient(),
            'created_at'           => $m->created_at?->format('M d, Y g:i A'),
            'deleted_for_everyone' => $m->isDeletedForEveryone(),
            'reactions'            => $m->reactionSummary(),
        ];
    }
}