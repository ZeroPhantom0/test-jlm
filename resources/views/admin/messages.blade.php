@extends('layouts.admin', ['active' => 'messages', 'pageTitle' => 'Messages'])
@section('title', 'Messages')

@section('content')
<div class="p-4 md:p-8 max-w-5xl mx-auto space-y-5">

    <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
        <div>
            <div class="flex items-center gap-2.5 text-xl font-extrabold text-slate-900">
                <i class="fa-regular fa-comment-dots text-emerald-500 text-lg"></i>
                <span>Messages</span>
            </div>
            <p class="text-[13px] text-slate-500 mt-1">View and respond to tenant messages</p>
        </div>
        <button id="admin-new-message-btn" class="bg-[#041d40] hover:bg-[#0d2a52] text-white px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2 transition">
            <i class="fa-solid fa-pen"></i> New Message
        </button>
    </div>

    <div class="flex items-center justify-between text-[13px]">
        <span class="text-slate-500" id="admin-msg-count">Showing {{ $total }} of {{ $total }} conversation(s)</span>
        <span class="{{ $unreadCount > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">
            {{ $unreadCount }} unread message(s)
        </span>
    </div>

    {{-- LIST VIEW --}}
    <div id="admin-messages-list-view" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center gap-2.5 border-b border-slate-100">
            <button class="tab-btn active bg-[#041d40] text-white px-4 py-2 rounded-[10px] text-[13px] font-semibold" data-tab="all">All Messages</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50" data-tab="unread">Unread</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50" data-tab="read">Read</button>
        </div>

        <div class="px-5 py-4 border-b border-slate-100">
            <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" id="admin-conv-search" placeholder="Search by tenant name or subject..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
        </div>

        <div id="admin-conversations-container" class="flex flex-col">
            @forelse($conversations as $c)
                @php
                    $latest = $c->latestMessage;
                    $unread = $c->messages()->whereNull('read_at')->where('sender_role', 'tenant')->count();
                @endphp
                <div class="admin-conversation-item px-5 py-4 border-b border-slate-50 last:border-0 flex gap-4 cursor-pointer hover:bg-slate-50 transition"
                     data-conv-id="{{ $c->id }}"
                     data-unread="{{ $unread > 0 ? 'true' : 'false' }}"
                     data-search="{{ strtolower(($c->tenant->user->name ?? '') . ' ' . $c->subject) }}">
                    <div class="w-11 h-11 rounded-xl {{ $unread > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center text-[16px] shrink-0">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-sm font-bold text-slate-900">{{ $c->tenant->user->name ?? 'Unknown' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $c->last_message_at?->format('M d, h:i A') ?? '' }}</div>
                        </div>
                        <div class="text-xs text-slate-500 mb-1.5">{{ $c->subject }}</div>
                        <div class="text-[12.5px] text-slate-600 leading-relaxed mb-2 line-clamp-2">
                            {{ $latest?->body ?? 'No messages yet.' }}
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-[11px] text-slate-400">{{ $c->messages()->count() }} message(s)</div>
                            @if($unread > 0)
                                <span class="bg-[#e06800] text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $unread }} new</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-slate-400">
                    <i class="fa-regular fa-comment-dots text-3xl mb-2 block"></i>
                    <p class="text-sm">No conversations yet.</p>
                </div>
            @endforelse
        </div>

        <div id="admin-empty-unread" class="hidden flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-3xl mb-4">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <h4 class="text-[15px] font-bold text-slate-900 mb-1">No messages found</h4>
            <p class="text-[13px] text-slate-500">All messages have been read</p>
        </div>
    </div>

    {{-- NEW MESSAGE VIEW --}}
    <div id="admin-messages-new-view" class="hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <button id="admin-messages-back-btn" class="text-slate-700 text-base"><i class="fa-solid fa-arrow-left"></i></button>
                <h3 class="text-base font-bold text-slate-900">New Message</h3>
            </div>
            <form id="admin-new-message-form" class="p-6 flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-700">To Tenant *</label>
                        <select id="admin-new-tenant" required class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px] outline-none">
                            <option value="">Select a tenant</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t['id'] }}" data-email="{{ $t['email'] }}" data-phone="{{ $t['phone'] }}">
                                    {{ $t['name'] }} ({{ $t['email'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-700">Mobile Number</label>
                        <input type="text" id="admin-new-phone" readonly class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-[13px] outline-none">
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-700">Subject *</label>
                    <input type="text" id="admin-new-subject" required placeholder="Enter message subject..." class="bg-white border border-slate-200 rounded-xl px-4 py-3 text-[13px] outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-700">Message *</label>
                    <textarea id="admin-new-body" required placeholder="Type your message..." class="bg-white border border-slate-200 rounded-xl px-4 py-3 text-[13px] outline-none resize-y min-h-[180px]"></textarea>
                </div>
                <div class="px-5 pb-5 flex justify-between items-center">
                    <button type="button" id="admin-messages-cancel-btn" class="text-slate-600 text-sm font-semibold px-4 py-2.5">Cancel</button>
                    <button type="submit" id="admin-send-btn" class="bg-[#041d40] hover:bg-[#0d2a52] text-white px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2 disabled:opacity-50">
                        <i class="fa-regular fa-paper-plane"></i>
                        <span id="admin-send-label">Send Message</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- THREAD VIEW --}}
    <div id="admin-messages-thread-view" class="hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-start gap-3">
                <button id="admin-thread-back-btn" class="text-slate-700 text-base mt-0.5">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="flex flex-col flex-1 min-w-0">
                    <div id="admin-thread-tenant" class="text-base font-bold text-slate-900">—</div>
                    <div id="admin-thread-subject" class="text-xs text-slate-400 mt-0.5">—</div>
                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-regular fa-clock"></i>
                        <span id="admin-thread-count">0 message(s)</span>
                    </div>
                </div>
                <button id="admin-delete-conversation-btn"
                        class="text-rose-500 hover:text-rose-700 text-[12px] font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-trash-can"></i> Delete Conversation
                </button>
            </div>

            <div id="admin-thread-messages" class="p-6 flex flex-col gap-4 min-h-[220px] max-h-[500px] overflow-y-auto"></div>

            <div class="px-5 pb-5 pt-2 flex items-end gap-3 border-t border-slate-100">
                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <textarea id="admin-reply-textarea" placeholder="Type your reply..."
                              class="w-full bg-transparent border-none outline-none text-[13px] resize-none h-12 text-slate-900"></textarea>
                </div>
                <button id="admin-reply-send-btn" class="bg-[#02122b] hover:bg-[#0d2347] text-white rounded-xl px-6 py-4 text-sm font-semibold inline-flex items-center gap-2 transition disabled:opacity-50">
                    <i class="fa-regular fa-paper-plane"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>

{{-- React Modal Mount Point (empty div — React only renders the confirmation modals here) --}}
<div id="admin-messages-root"></div>
@endsection

@push('styles')
<style>
    .bubble-wrap { position: relative; max-width: 85%; }
    .bubble-wrap.self { align-self: flex-end; }
    .bubble-wrap.them { align-self: flex-start; }
    .bubble-wrap:hover .bubble-actions { opacity: 1; pointer-events: auto; }
    .bubble-actions {
        position: absolute; top: -14px;
        display: flex; gap: 4px;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 999px; padding: 3px 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,.08);
        opacity: 0; pointer-events: none;
        transition: opacity .15s;
        z-index: 5;
    }
    .bubble-wrap.self .bubble-actions { right: 0; }
    .bubble-wrap.them .bubble-actions { left: 0; }
    .bubble-actions button {
        width: 24px; height: 24px; display: inline-flex;
        align-items: center; justify-content: center;
        border-radius: 50%; cursor: pointer;
        background: transparent; border: none;
        font-size: 14px; line-height: 1;
        transition: background .15s, transform .15s;
    }
    .bubble-actions button:hover { background: #f1f5f9; transform: scale(1.15); }
    .reaction-pill {
        display: inline-flex; align-items: center; gap: 3px;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 999px; padding: 2px 8px;
        font-size: 11px; font-weight: 600;
        margin-top: 4px; cursor: pointer;
    }
    .reaction-pill:hover { background: #f8fafc; }
    .bubble { border-radius: 16px; padding: 16px; line-height: 1.5; font-size: 13px; }
    .bubble.self  { background-color: #f97316; color: #ffffff; }
    .bubble.them  { background-color: #f1f5f9; color: #1e293b; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const listView   = document.getElementById('admin-messages-list-view');
    const formView   = document.getElementById('admin-messages-new-view');
    const threadView = document.getElementById('admin-messages-thread-view');

    let currentConvId   = null;
    let currentMessages = [];

    const EMOJIS = ['❤️', '👍', '😂', '😮', '😢', '🙏'];

    function showView(view) {
        [listView, formView, threadView].forEach(v => v.classList.add('hidden'));
        view.classList.remove('hidden');
    }

    document.getElementById('admin-new-message-btn').onclick    = () => showView(formView);
    document.getElementById('admin-messages-back-btn').onclick  = () => showView(listView);
    document.getElementById('admin-messages-cancel-btn').onclick = () => showView(listView);
    document.getElementById('admin-thread-back-btn').onclick    = () => showView(listView);

    document.getElementById('admin-new-tenant').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        document.getElementById('admin-new-phone').value = opt.dataset.phone || '';
    });

    document.querySelectorAll('.admin-conversation-item').forEach(item => {
        item.addEventListener('click', () => openThread(item.dataset.convId, item));
    });

    async function openThread(id, item) {
        currentConvId = id;
        try {
            const res = await fetch(`/admin/messages/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load.');

            currentMessages = data.data.messages;

            document.getElementById('admin-thread-tenant').innerText  = data.data.tenant;
            document.getElementById('admin-thread-subject').innerText = data.data.subject;
            document.getElementById('admin-thread-count').innerText   = `${data.data.messages.length} message(s) in this conversation`;

            renderMessages(data.data.messages);

            if (item && item.dataset.unread === 'true') {
                item.dataset.unread = 'false';
                item.querySelector('.w-11').classList.remove('bg-amber-100', 'text-amber-600');
                item.querySelector('.w-11').classList.add('bg-slate-100', 'text-slate-500');
                const badge = item.querySelector('.bg-\\[\\#e06800\\]');
                if (badge) badge.remove();
            }

            // Wire up "Delete Conversation" to open the React confirmation modal
            const delConvBtn = document.getElementById('admin-delete-conversation-btn');
            delConvBtn.onclick = () => {
                document.dispatchEvent(new CustomEvent('open-delete-conv-modal', {
                    detail: { id: id, tenant: data.data.tenant }
                }));
            };

            showView(threadView);
        } catch (err) {
            alert(err.message);
        }
    }

    function renderMessages(messages) {
        const container = document.getElementById('admin-thread-messages');
        container.innerHTML = '';

        messages.forEach(m => {
            const isAdmin = m.role === 'admin';

            const wrapper = document.createElement('div');
            wrapper.className = `bubble-wrap ${isAdmin ? 'self' : 'them'}`;
            wrapper.dataset.messageId = m.id;

            const bubble = document.createElement('div');
            bubble.className = `bubble ${isAdmin ? 'self' : 'them'}`;

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
                const reactionRow = document.createElement('div');
                reactionRow.className = 'flex flex-wrap gap-1 mt-1';
                Object.entries(m.reactions).forEach(([emoji, count]) => {
                    const pill = document.createElement('span');
                    pill.className = 'reaction-pill';
                    pill.textContent = `${emoji} ${count}`;
                    pill.onclick = (e) => { e.stopPropagation(); reactTo(m.id, emoji); };
                    reactionRow.appendChild(pill);
                });
                wrapper.appendChild(reactionRow);
            }

            container.appendChild(wrapper);
        });

        container.scrollTop = container.scrollHeight;
    }

    function deleteMessage(messageId) {
        const msg = currentMessages.find(m => m.id == messageId);
        if (!msg) return;

        document.dispatchEvent(new CustomEvent('open-delete-msg-modal', {
            detail: { id: messageId, body: msg.body }
        }));
    }

    async function reactTo(messageId, emoji) {
        try {
            const res = await fetch(`/admin/messages/${messageId}/react`, {
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
        } catch (err) {
            alert(err.message);
        }
    }

    document.getElementById('admin-new-message-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn   = document.getElementById('admin-send-btn');
        const label = document.getElementById('admin-send-label');
        const payload = {
            tenant_id: document.getElementById('admin-new-tenant').value,
            subject:   document.getElementById('admin-new-subject').value,
            body:      document.getElementById('admin-new-body').value,
        };
        if (!payload.tenant_id || !payload.subject.trim() || !payload.body.trim()) {
            alert('Please fill in all fields.');
            return;
        }
        btn.disabled = true; label.innerText = 'Sending…';
        try {
            const res = await fetch('/admin/messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to send.');
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            btn.disabled = false; label.innerText = 'Send Message';
        }
    });

    document.getElementById('admin-reply-send-btn').addEventListener('click', async () => {
        const ta   = document.getElementById('admin-reply-textarea');
        const body = ta.value.trim();
        if (!body || !currentConvId) return;
        const btn = document.getElementById('admin-reply-send-btn');
        btn.disabled = true;
        try {
            const res = await fetch(`/admin/messages/${currentConvId}/reply`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ body }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to send.');
            ta.value = '';
            const item = document.querySelector(`.admin-conversation-item[data-conv-id="${currentConvId}"]`);
            openThread(currentConvId, item);
        } catch (err) {
            alert(err.message);
        } finally {
            btn.disabled = false;
        }
    });

    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.addEventListener('click', () => {
        tabBtns.forEach(b => { b.classList.remove('active', 'bg-[#041d40]', 'text-white'); b.classList.add('text-slate-600', 'hover:bg-slate-50'); });
        btn.classList.add('active', 'bg-[#041d40]', 'text-white');
        btn.classList.remove('text-slate-600', 'hover:bg-slate-50');
        filterConversations(btn.dataset.tab);
    }));

    document.getElementById('admin-conv-search').addEventListener('input', () => {
        const active = document.querySelector('.tab-btn.active')?.dataset.tab || 'all';
        filterConversations(active);
    });

    function filterConversations(tab) {
        const q = (document.getElementById('admin-conv-search').value || '').toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('.admin-conversation-item').forEach(item => {
            const isUnread = item.dataset.unread === 'true';
            const matchesTab = tab === 'all' || (tab === 'unread' && isUnread) || (tab === 'read' && !isUnread);
            const matchesSearch = !q || (item.dataset.search || '').includes(q);
            const show = matchesTab && matchesSearch;
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const empty = document.getElementById('admin-empty-unread');
        if (visible === 0 && tab === 'unread') { empty.classList.remove('hidden'); empty.classList.add('flex'); }
        else { empty.classList.add('hidden'); empty.classList.remove('flex'); }
    }

    function escapeHTML(str) {
        return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s]);
    }

    document.addEventListener('conv-deleted', (e) => {
        const { id } = e.detail;
        const item = document.querySelector(`.admin-conversation-item[data-conv-id="${id}"]`);
        if (item) item.remove();
        showView(listView);
    });

    document.addEventListener('msg-deleted', (e) => {
        const { id } = e.detail;
        currentMessages = currentMessages.filter(x => x.id != id);
        renderMessages(currentMessages);
    });
})();
</script>
@endpush