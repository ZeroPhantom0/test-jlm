@extends('layouts.tenant', ['active' => 'messages', 'pageTitle' => 'Messages'])
@section('title', 'Messages')

@section('content')
<div class="p-4 md:p-8 max-w-5xl mx-auto" id="messages-root">

    {{-- HEADER --}}
    <div class="flex justify-between items-start mb-4">
        <div>
            <div class="flex items-center gap-2.5 text-xl font-bold text-slate-900">
                <i class="fa-regular fa-comment-dots text-emerald-500 text-lg"></i>
                <span>Messages</span>
            </div>
            <p class="text-[13px] text-slate-500 mt-1">Communicate with building management</p>
        </div>
        <button id="new-message-btn" class="bg-[#041d40] hover:bg-[#0d2a52] text-white px-4 py-2.5 rounded-full text-[13px] font-semibold inline-flex items-center gap-2">
            <i class="fa-solid fa-pen"></i> New Message
        </button>
    </div>

    {{-- LIST VIEW --}}
    <div id="messages-list-view">
        <div class="flex justify-between items-center text-[13px] mb-4">
            <span class="text-slate-500" id="showingCountText">Showing {{ $total }} of {{ $total }} conversation(s)</span>
            <span class="{{ $unreadCount > 0 ? 'text-[#e06800] font-bold' : 'text-slate-400' }}" id="unreadCountText">
                {{ $unreadCount }} unread message(s)
            </span>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 pt-4 flex items-center gap-2">
                <button class="tab-btn active" data-tab="all">All Messages</button>
                <button class="tab-btn" data-tab="unread">Unread</button>
                <button class="tab-btn" data-tab="read">Read</button>
            </div>

            <div class="p-4 border-b border-slate-100 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-7 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="conversation-search" placeholder="Search by subject..."
                       class="w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-lg text-[13px] outline-none focus:border-[#041d40]">
            </div>

            <div id="conversationsContainer" class="flex flex-col">
                @forelse($conversations as $c)
                    @php
                        $latest = $c->latestMessage;
                        $unread = $c->messages()->whereNull('read_at')->where('sender_role', 'admin')->count();
                    @endphp
                    <div class="conversation-item p-5 border-b border-slate-100 last:border-0 flex gap-4 cursor-pointer hover:bg-slate-50 transition"
                         data-conv-id="{{ $c->id }}"
                         data-unread="{{ $unread > 0 ? 'true' : 'false' }}"
                         data-subject="{{ strtolower($c->subject) }}">
                        <div class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0 {{ $unread > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-[#041d40]' }} text-lg">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1.5">
                                <span class="text-sm font-bold text-slate-900">{{ $c->subject }}</span>
                                <span class="text-xs text-slate-400">{{ $c->last_message_at?->format('M d') ?? '' }}</span>
                            </div>
                            <div class="text-[13px] text-slate-600 leading-relaxed mb-2 line-clamp-2">
                                {{ $latest?->body ?? 'No messages yet.' }}
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-400">Building Management</span>
                                @if($unread > 0)
                                    <span class="bg-[#e06800] text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full">{{ $unread }} new</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-slate-400">
                        <i class="fa-regular fa-comment-dots text-3xl mb-2 block"></i>
                        <p class="text-sm">No messages yet. Click "New Message" to start a conversation.</p>
                    </div>
                @endforelse
            </div>

            <div id="emptyState" class="hidden flex-col items-center justify-center py-20 px-5 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mb-4">
                    <i class="fa-solid fa-inbox"></i>
                </div>
                <h4 class="text-[15px] font-bold text-slate-900 mb-1">No messages found</h4>
                <p class="text-[13px] text-slate-500" id="emptyStateText">All messages have been read</p>
            </div>
        </div>
    </div>

    {{-- NEW MESSAGE VIEW --}}
    <div id="messages-form-view" class="hidden">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <button id="messages-back-btn" class="text-slate-700 text-base"><i class="fa-solid fa-arrow-left"></i></button>
                <h4 class="text-base font-bold text-slate-900">New Message</h4>
            </div>
            <form id="new-message-form" class="p-6 flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">To</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-[13px] text-slate-600">
                        <i class="fa-solid fa-building mr-2"></i> Building Management
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">Subject *</label>
                    <input type="text" id="new-subject" required class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none focus:border-[#041d40]" placeholder="Enter message subject...">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">Message *</label>
                    <textarea id="new-body" required class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none resize-none min-h-[180px] focus:border-[#041d40]" placeholder="Type your message..."></textarea>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <button type="button" id="messages-cancel-btn" class="text-slate-600 text-sm font-semibold px-4 py-2.5">Cancel</button>
                    <button type="submit" id="tenant-send-btn" class="bg-[#041d40] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 disabled:opacity-50">
                        <i class="fa-regular fa-paper-plane"></i> <span id="tenant-send-label">Send Message</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- THREAD VIEW — ADDED: Delete Conversation button on the right --}}
    <div id="messages-thread-view" class="hidden">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-start gap-3">
                <button id="thread-back-btn" class="text-slate-700 text-base mt-1"><i class="fa-solid fa-arrow-left"></i></button>
                <div class="flex flex-col flex-1 min-w-0">
                    <span class="text-base font-bold text-slate-900">Building Management</span>
                    <span class="text-[12px] text-slate-400 mt-0.5" id="threadSubject">—</span>
                    <span class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                        <i class="fa-regular fa-clock"></i>
                        <span id="threadCountText">0 message(s)</span>
                    </span>
                </div>
                {{-- NEW: Delete Conversation button --}}
                <button id="delete-conversation-btn"
                        class="text-rose-500 hover:text-rose-700 text-[12px] font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-trash-can"></i> Delete Conversation
                </button>
            </div>

            <div class="px-5 py-6 flex flex-col gap-4 min-h-[220px] max-h-[500px] overflow-y-auto" id="threadMessagesContainer"></div>

            <div class="px-5 pb-6 pt-2 flex items-end gap-3 border-t border-slate-100">
                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <textarea id="reply-textarea" placeholder="Type your reply..."
                              class="w-full border-none bg-transparent outline-none text-[13px] resize-none h-12 text-slate-900"></textarea>
                </div>
                <button id="reply-send-btn" class="bg-[#041d40] text-white px-6 py-4 rounded-xl text-[14px] font-semibold inline-flex items-center gap-2 disabled:opacity-50">
                    <i class="fa-regular fa-paper-plane"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tab-btn {
        padding: 8px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 600;
        color: #475569; background: transparent;
        border: none; cursor: pointer;
        transition: all 0.2s ease;
    }
    .tab-btn.active { background-color: #041d40; color: #ffffff; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .conversation-item.is-hidden { display: none; }

    .bubble-wrap { position: relative; max-width: 85%; }
    .bubble-wrap.self { align-self: flex-end; }
    .bubble-wrap.them { align-self: flex-start; }
    .bubble-wrap:hover .bubble-actions { opacity: 1; pointer-events: auto; }
    .bubble-actions {
        position: absolute; top: -14px; display: flex; gap: 4px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: 3px 6px; box-shadow: 0 4px 12px rgba(0,0,0,.08);
        opacity: 0; pointer-events: none; transition: opacity .15s; z-index: 5;
    }
    .bubble-wrap.them .bubble-actions { left: 0; }
    .bubble-wrap.self .bubble-actions { right: 0; }
    .bubble-actions button {
        width: 24px; height: 24px; display: inline-flex;
        align-items: center; justify-content: center;
        border-radius: 50%; cursor: pointer; background: transparent;
        border: none; font-size: 14px; line-height: 1;
        transition: background .15s, transform .15s;
    }
    .bubble-actions button:hover { background: #f1f5f9; transform: scale(1.15); }
    .reaction-pill {
        display: inline-flex; align-items: center; gap: 3px;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 999px; padding: 2px 8px;
        font-size: 11px; font-weight: 600; margin-top: 4px; cursor: pointer;
    }
    .reaction-pill:hover { background: #f8fafc; }
    .bubble { border-radius: 16px; padding: 16px; line-height: 1.5; font-size: 13px; }
    .bubble.tenant { background-color: #f1f5f9; color: #1e293b; }
    .bubble.admin  { background-color: #f97316; color: #ffffff; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const listView   = document.getElementById('messages-list-view');
    const formView   = document.getElementById('messages-form-view');
    const threadView = document.getElementById('messages-thread-view');

    let currentConvId   = null;
    let currentMessages = [];

    const EMOJIS = ['❤️', '👍', '😂', '😮', '😢', '🙏'];

    const showView = (v) => {
        [listView, formView, threadView].forEach(x => x.classList.add('hidden'));
        v.classList.remove('hidden');
    };

    document.getElementById('new-message-btn').onclick      = () => showView(formView);
    document.getElementById('messages-back-btn').onclick    = () => showView(listView);
    document.getElementById('messages-cancel-btn').onclick  = () => showView(listView);
    document.getElementById('thread-back-btn').onclick      = () => showView(listView);

    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', () => openThread(item.dataset.convId, item));
    });

    async function openThread(id, item) {
        currentConvId = id;
        try {
            const res = await fetch(`/tenants/messages/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load.');

            currentMessages = data.data.messages;

            document.getElementById('threadSubject').innerText   = data.data.subject;
            document.getElementById('threadCountText').innerText = `${data.data.messages.length} message(s) in this conversation`;

            renderMessages(data.data.messages);

            if (item && item.dataset.unread === 'true') {
                item.dataset.unread = 'false';
                const iconBox = item.querySelector('.w-11');
                iconBox.classList.remove('bg-amber-100', 'text-amber-600');
                iconBox.classList.add('bg-slate-100', 'text-[#041d40]');
                const badge = item.querySelector('.bg-\\[\\#e06800\\]');
                if (badge) badge.remove();
            }

            // NEW: Wire up "Delete Conversation" for this thread
            const delConvBtn = document.getElementById('delete-conversation-btn');
            delConvBtn.onclick = async () => {
                if (!confirm('Delete this whole conversation from your inbox? The admin will still see it unless they also delete it.')) return;
                try {
                    const r = await fetch(`/tenants/messages/conversation/${id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    });
                    const d = await r.json();
                    if (!r.ok) throw new Error(d.message || 'Failed to delete.');
                    if (item) item.remove();
                    showView(listView);
                } catch (e) {
                    alert(e.message);
                }
            };

            showView(threadView);
        } catch (err) { alert(err.message); }
    }

    function renderMessages(messages) {
        const container = document.getElementById('threadMessagesContainer');
        container.innerHTML = '';

        messages.forEach(m => {
            const isAdmin = m.role === 'admin';

            const wrapper = document.createElement('div');
            wrapper.className = `bubble-wrap ${isAdmin ? 'them' : 'self'}`;
            wrapper.dataset.messageId = m.id;

            const bubble = document.createElement('div');
            bubble.className = `bubble ${isAdmin ? 'admin' : 'tenant'}`;

            // NEW: show placeholder when "deleted for everyone"
            bubble.innerHTML = m.deleted_for_everyone
                ? `
                    <div class="flex items-center justify-between mb-1.5 gap-6">
                        <span class="text-[11px] font-bold">${escapeHTML(m.sender)}</span>
                        <span class="text-[11px] opacity-80 whitespace-nowrap">${escapeHTML(m.created_at || '')}</span>
                    </div>
                    <div class="italic opacity-70">
                        <i class="fa-regular fa-trash-can mr-1"></i> This message was deleted.
                    </div>
                `
                : `
                    <div class="flex items-center justify-between mb-1.5 gap-6">
                        <span class="text-[11px] font-bold">${escapeHTML(m.sender)}</span>
                        <span class="text-[11px] opacity-80 whitespace-nowrap">${escapeHTML(m.created_at || '')}</span>
                    </div>
                    <div>${escapeHTML(m.body)}</div>
                `;

            const actions = document.createElement('div');
            actions.className = 'bubble-actions';
            EMOJIS.forEach(emoji => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = emoji;
                b.title = `React ${emoji}`;
                b.onclick = (e) => { e.stopPropagation(); reactTo(m.id, emoji); };
                actions.appendChild(b);
            });
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.innerHTML = '<i class="fa-regular fa-trash-can" style="font-size:11px;"></i>';
            delBtn.title = 'Delete message';
            delBtn.onclick = (e) => { e.stopPropagation(); deleteMessage(m.id); };
            actions.appendChild(delBtn);

            wrapper.appendChild(actions);
            wrapper.appendChild(bubble);

            if (m.reactions && Object.keys(m.reactions).length > 0) {
                const row = document.createElement('div');
                row.className = 'flex flex-wrap gap-1 mt-1';
                Object.entries(m.reactions).forEach(([emoji, count]) => {
                    const pill = document.createElement('span');
                    pill.className = 'reaction-pill';
                    pill.textContent = `${emoji} ${count}`;
                    pill.onclick = (e) => { e.stopPropagation(); reactTo(m.id, emoji); };
                    row.appendChild(pill);
                });
                wrapper.appendChild(row);
            }

            container.appendChild(wrapper);
        });

        container.scrollTop = container.scrollHeight;
    }

    async function deleteMessage(messageId) {
        if (!confirm('Delete this message for you? The other side will still see it.')) return;
        try {
            const res = await fetch(`/tenants/messages/${messageId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete.');

            currentMessages = currentMessages.filter(x => x.id != messageId);
            renderMessages(currentMessages);
        } catch (err) { alert(err.message); }
    }

    async function reactTo(messageId, emoji) {
        try {
            const res = await fetch(`/tenants/messages/${messageId}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ reaction: emoji }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to react.');

            const idx = currentMessages.findIndex(x => x.id == messageId);
            if (idx > -1) {
                currentMessages[idx].reactions = data.reactions;
                renderMessages(currentMessages);
            }
        } catch (err) { alert(err.message); }
    }

    document.getElementById('new-message-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('tenant-send-btn');
        const label = document.getElementById('tenant-send-label');

        const payload = {
            subject: document.getElementById('new-subject').value,
            body:    document.getElementById('new-body').value,
        };

        if (!payload.subject.trim() || !payload.body.trim()) {
            alert('Please fill in all fields.');
            return;
        }

        btn.disabled = true;
        label.innerText = 'Sending…';

        try {
            const res = await fetch('/tenants/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to send.');
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            btn.disabled = false;
            label.innerText = 'Send Message';
        }
    });

    document.getElementById('reply-send-btn').addEventListener('click', async () => {
        const ta = document.getElementById('reply-textarea');
        const body = ta.value.trim();
        if (!body || !currentConvId) return;

        const btn = document.getElementById('reply-send-btn');
        btn.disabled = true;

        try {
            const res = await fetch(`/tenants/messages/${currentConvId}/reply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ body }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to send.');
            ta.value = '';
            const item = document.querySelector(`.conversation-item[data-conv-id="${currentConvId}"]`);
            openThread(currentConvId, item);
        } catch (err) {
            alert(err.message);
        } finally {
            btn.disabled = false;
        }
    });

    const tabButtons = document.querySelectorAll('.tab-btn');
    let currentTab = 'all';

    tabButtons.forEach(btn => btn.addEventListener('click', () => {
        tabButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentTab = btn.dataset.tab;
        applyFilter(currentTab);
    }));

    document.getElementById('conversation-search').addEventListener('input', () => applyFilter(currentTab));

    function applyFilter(tab) {
        const q = (document.getElementById('conversation-search').value || '').toLowerCase().trim();
        const items = document.querySelectorAll('.conversation-item');
        let visible = 0;

        items.forEach(item => {
            const isUnread = item.dataset.unread === 'true';
            const matchesTab =
                tab === 'all' ||
                (tab === 'unread' && isUnread) ||
                (tab === 'read' && !isUnread);
            const matchesSearch = !q || (item.dataset.subject || '').includes(q);

            const show = matchesTab && matchesSearch;
            item.classList.toggle('is-hidden', !show);
            if (show) visible++;
        });

        const empty = document.getElementById('emptyState');
        if (visible === 0) {
            empty.classList.remove('hidden');
            empty.classList.add('flex');
            document.getElementById('emptyStateText').innerText =
                tab === 'unread' ? 'All messages have been read' : 'No messages found';
        } else {
            empty.classList.add('hidden');
            empty.classList.remove('flex');
        }
    }

    function escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[s]);
    }
})();
</script>
@endpush