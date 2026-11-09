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
            <button id="new-message-btn"
                class="bg-[#041d40] hover:bg-[#0d2a52] text-white px-4 py-2.5 rounded-full text-[13px] font-semibold inline-flex items-center gap-2">
                <i class="fa-solid fa-pen"></i> New Message
            </button>
        </div>

        {{-- LIST VIEW --}}
        <div id="messages-list-view">
            <div class="flex justify-between items-center text-[13px] mb-4">
                <span class="text-slate-500" id="showingCountText">Showing {{ $total }} of {{ $total }}
                    conversation(s)</span>
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
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-7 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="conversation-search" placeholder="Search by subject..."
                        class="w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-lg text-[13px] outline-none focus:border-[#041d40]">
                </div>

                <div id="conversationsContainer" class="flex flex-col">
                    @forelse($conversations as $c)
                        @php
                            $latest = $c->latestMessage;
                            $unread = $c->messages()->whereNull('read_at')->where('sender_role', 'admin')->count();
                        @endphp
                        <div class="conversation-item conv-row p-5 border-b border-slate-100 last:border-0 flex gap-4 cursor-pointer hover:bg-slate-50 transition {{ $unread > 0 ? 'bg-amber-50/40' : 'bg-white' }}"
                            data-conv-id="{{ $c->id }}" data-unread="{{ $unread > 0 ? 'true' : 'false' }}"
                            data-subject="{{ strtolower($c->subject) }}">
                            <div
                                class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0 {{ $unread > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-[#041d40]' }} text-lg">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline mb-1.5">
                                    <span class="text-sm font-bold text-slate-900">{{ $c->subject }}</span>
                                    <span
                                        class="text-xs text-slate-400">{{ $c->last_message_at?->format('M d') ?? '' }}</span>
                                </div>
                                <div class="text-[13px] text-slate-600 leading-relaxed mb-2 line-clamp-2">
                                    {{ $latest?->body ?: ($latest?->attachments ? '📎 Attachment' : 'No messages yet.') }}
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-400">Building Management</span>
                                    @if ($unread > 0)
                                        <span
                                            class="bg-[#e06800] text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full">{{ $unread }}
                                            new</span>
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
                    <div
                        class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mb-4">
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
                    <button id="messages-back-btn" class="text-slate-700 text-base"><i
                            class="fa-solid fa-arrow-left"></i></button>
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
                        <input type="text" id="new-subject" required
                            class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none focus:border-[#041d40]"
                            placeholder="Enter message subject...">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[13px] font-semibold text-slate-700">Message</label>
                        <textarea id="new-body"
                            class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none resize-none min-h-[180px] focus:border-[#041d40]"
                            placeholder="Type your message (optional if you attach a file)..."></textarea>
                    </div>

                    {{-- Attachment previews --}}
                    <div id="tenant-new-attachments-preview" class="flex flex-wrap gap-2 hidden"></div>

                    <div class="flex justify-between items-center pt-2">
                        <div class="flex items-center gap-2">
                            <button type="button" id="tenant-new-attach-btn"
                                class="text-slate-600 text-sm font-semibold px-3 py-2.5 border border-slate-200 rounded-lg hover:bg-slate-50 inline-flex items-center gap-2">
                                <i class="fa-solid fa-paperclip"></i> Attach
                            </button>
                            <input type="file" id="tenant-new-files" multiple hidden
                                accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                            <button type="button" id="messages-cancel-btn"
                                class="text-slate-600 text-sm font-semibold px-4 py-2.5">Cancel</button>
                        </div>
                        <button type="submit" id="tenant-send-btn"
                            class="bg-[#041d40] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 disabled:opacity-50">
                            <i class="fa-regular fa-paper-plane"></i> <span id="tenant-send-label">Send Message</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- THREAD VIEW --}}
        <div id="messages-thread-view" class="hidden">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-start gap-3">
                    <button id="thread-back-btn" class="text-slate-700 text-base mt-1"><i
                            class="fa-solid fa-arrow-left"></i></button>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-base font-bold text-slate-900">Building Management</span>
                        <span class="text-[12px] text-slate-400 mt-0.5" id="threadSubject">—</span>
                        <span class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i>
                            <span id="threadCountText">0 message(s)</span>
                        </span>
                    </div>
                    <button id="delete-conversation-btn"
                        class="text-rose-500 hover:text-rose-700 text-[12px] font-semibold inline-flex items-center gap-1.5">
                        <i class="fa-regular fa-trash-can"></i> Delete Conversation
                    </button>
                </div>

                <div class="px-5 py-6 flex flex-col gap-4 min-h-[220px] max-h-[500px] overflow-y-auto"
                    id="threadMessagesContainer"></div>

                <div class="px-5 pb-6 pt-2 border-t border-slate-100">
                    <!-- Reply attachment previews -->
                    <div id="tenant-reply-attachments-preview" class="flex flex-wrap gap-2 mb-3 hidden"></div>

                    <div class="flex items-end gap-3">
                        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                            <textarea id="reply-textarea" placeholder="Type your reply..."
                                class="w-full border-none bg-transparent outline-none text-[13px] resize-none h-12 text-slate-900"></textarea>
                        </div>
                        <button type="button" id="tenant-reply-attach-btn"
                            class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 px-4 py-4 rounded-xl text-[14px] font-semibold inline-flex items-center gap-2">
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <input type="file" id="tenant-reply-files" multiple hidden
                            accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                        <button id="reply-send-btn"
                            class="bg-[#041d40] text-white px-6 py-4 rounded-xl text-[14px] font-semibold inline-flex items-center gap-2 disabled:opacity-50">
                            <i class="fa-regular fa-paper-plane"></i> <span id="reply-send-label">Send</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .tab-btn {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background-color: #041d40;
            color: #ffffff;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .conversation-item.is-hidden {
            display: none;
        }

        .bubble-wrap {
            position: relative;
            max-width: 85%;
        }

        .bubble-wrap.self {
            align-self: flex-end;
        }

        .bubble-wrap.them {
            align-self: flex-start;
        }

        .bubble-wrap:hover .bubble-actions {
            opacity: 1;
            pointer-events: auto;
        }

        .bubble-actions {
            position: absolute;
            top: -14px;
            display: flex;
            gap: 4px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 3px 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s;
            z-index: 5;
        }

        .bubble-wrap.them .bubble-actions {
            left: 0;
        }

        .bubble-wrap.self .bubble-actions {
            right: 0;
        }

        .bubble-actions button {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            background: transparent;
            border: none;
            font-size: 14px;
            line-height: 1;
            transition: background .15s, transform .15s;
        }

        .bubble-actions button:hover {
            background: #f1f5f9;
            transform: scale(1.15);
        }

        .reaction-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
            cursor: pointer;
        }

        .reaction-pill:hover {
            background: #f8fafc;
        }

        .bubble {
            border-radius: 16px;
            padding: 16px;
            line-height: 1.5;
            font-size: 13px;
        }

        .bubble.tenant {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .bubble.admin {
            background-color: #f97316;
            color: #ffffff;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
            const listView = document.getElementById('messages-list-view');
            const formView = document.getElementById('messages-form-view');
            const threadView = document.getElementById('messages-thread-view');

            let currentConvId = null;
            let currentMessages = [];

            const EMOJIS = ['❤️', '👍', '😂', '😮', '😢', '🙏'];

            const showView = (v) => {
                [listView, formView, threadView].forEach(x => x.classList.add('hidden'));
                v.classList.remove('hidden');
            };

            document.getElementById('new-message-btn').onclick = () => {
                currentConvId = null;
                showView(formView);
            };
            document.getElementById('messages-back-btn').onclick = () => {
                currentConvId = null;
                showView(listView);
            };
            document.getElementById('messages-cancel-btn').onclick = () => {
                currentConvId = null;
                showView(listView);
            };
            document.getElementById('thread-back-btn').onclick = () => {
                currentConvId = null;
                showView(listView);
            };

            document.querySelectorAll('.conversation-item').forEach(item => {
                item.addEventListener('click', () => openThread(item.dataset.convId, item));
            });

            async function openThread(id, item) {
                currentConvId = id;
                try {
                    const res = await fetch(`/tenants/messages/${id}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Failed to load.');

                    currentMessages = data.data.messages;

                    document.getElementById('threadSubject').innerText = data.data.subject;
                    document.getElementById('threadCountText').innerText =
                        `${data.data.messages.length} message(s) in this conversation`;

                    renderMessages(data.data.messages);

                    if (item && item.dataset.unread === 'true') {
                        item.dataset.unread = 'false';
                        item.classList.remove('bg-amber-50/40');
                        item.classList.add('bg-white');

                        const iconBox = item.querySelector('.w-11');
                        iconBox.classList.remove('bg-amber-100', 'text-amber-600');
                        iconBox.classList.add('bg-slate-100', 'text-[#041d40]');

                        // Remove all badges when read
                        item.querySelectorAll('.badge-unread').forEach(b => b.remove());

                        updateTenantGlobalUnreadCount();
                    }

                    const delConvBtn = document.getElementById('delete-conversation-btn');
                    delConvBtn.onclick = async () => {
                        if (!confirm(
                                'Delete this whole conversation from your inbox? The admin will still see it unless they also delete it.'
                            )) return;
                        try {
                            const r = await fetch(`/tenants/messages/conversation/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf()
                                },
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
                } catch (err) {
                    alert(err.message);
                }
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

                    const attachmentsHTML = renderAttachments(m.attachments || []);

                    bubble.innerHTML = m.deleted_for_everyone ?
                        `
                            <div class="flex items-center justify-between mb-1.5 gap-6">
                                <span class="text-[11px] font-bold">${escapeHTML(m.sender)}</span>
                                <span class="text-[11px] opacity-80 whitespace-nowrap">${escapeHTML(m.created_at || '')}</span>
                            </div>
                            <div class="italic opacity-70">
                                <i class="fa-regular fa-trash-can mr-1"></i> This message was deleted.
                            </div>
                        ` :
                        `
                            <div class="flex items-center justify-between mb-1.5 gap-6">
                                <span class="text-[11px] font-bold">${escapeHTML(m.sender)}</span>
                                <span class="text-[11px] opacity-80 whitespace-nowrap">${escapeHTML(m.created_at || '')}</span>
                            </div>
                            ${attachmentsHTML}
                            ${m.body && m.body.trim() !== '' ? `<div class="whitespace-pre-wrap">${escapeHTML(m.body)}</div>` : ''}
                        `;

                    const actions = document.createElement('div');
                    actions.className = 'bubble-actions';
                    EMOJIS.forEach(emoji => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.textContent = emoji;
                        b.title = `React ${emoji}`;
                        b.onclick = (e) => {
                            e.stopPropagation();
                            reactTo(m.id, emoji);
                        };
                        actions.appendChild(b);
                    });
                    const delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.innerHTML = '<i class="fa-regular fa-trash-can" style="font-size:11px;"></i>';
                    delBtn.title = 'Delete message';
                    delBtn.onclick = (e) => {
                        e.stopPropagation();
                        deleteMessage(m.id);
                    };
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
                            pill.onclick = (e) => {
                                e.stopPropagation();
                                reactTo(m.id, emoji);
                            };
                            row.appendChild(pill);
                        });
                        wrapper.appendChild(row);
                    }

                    container.appendChild(wrapper);
                });

                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 50);
            }

            function renderAttachments(attachments) {
                if (!attachments || attachments.length === 0) return '';

                const imgs = attachments.filter(a => a.is_image);
                const files = attachments.filter(a => !a.is_image);

                let html = '';

                if (imgs.length > 0) {
                    html += '<div class="flex flex-wrap gap-1.5 mb-2">';
                    imgs.forEach((a, idx) => {
                        html += `
                            <button type="button"
                                    class="fb-attach-thumb group relative overflow-hidden rounded-lg border border-white/20"
                                    data-group="${escapeHTML(a.name + '-' + idx)}"
                                    data-index="${idx}"
                                    data-name="${escapeHTML(a.name)}"
                                    data-src="${a.url}">
                                <img src="${a.url}" alt="${escapeHTML(a.name)}"
                                     class="max-h-48 max-w-[220px] object-cover transition group-hover:brightness-90"
                                     loading="lazy">
                            </button>
                        `;
                    });
                    html += '</div>';
                }

                if (files.length > 0) {
                    html += '<div class="flex flex-col gap-1.5 mb-2">';
                    files.forEach(a => {
                        const size = a.size > 1024 * 1024 ?
                            (a.size / (1024 * 1024)).toFixed(1) + ' MB' :
                            Math.max(1, Math.round(a.size / 1024)) + ' KB';
                        html += `
                            <a href="${a.url}" download="${escapeHTML(a.name)}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg border border-white/20 hover:bg-white/10 transition text-[12px]">
                                <i class="fa-solid fa-paperclip"></i>
                                <span class="truncate max-w-[200px]">${escapeHTML(a.name)}</span>
                                <span class="opacity-70">${size}</span>
                                <i class="fa-solid fa-download ml-auto opacity-60"></i>
                            </a>
                        `;
                    });
                    html += '</div>';
                }

                return html;
            }

            async function deleteMessage(messageId) {
                if (!confirm('Delete this message for you? The other side will still see it.')) return;
                try {
                    const res = await fetch(`/tenants/messages/${messageId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf()
                        },
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Failed to delete.');

                    currentMessages = currentMessages.filter(x => x.id != messageId);
                    renderMessages(currentMessages);
                } catch (err) {
                    alert(err.message);
                }
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
                        body: JSON.stringify({
                            reaction: emoji
                        }),
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

            const tenantSelected = {
                new: [],
                reply: []
            };

            async function compressImage(file) {
                if (!file.type.startsWith('image/') || file.size < 200 * 1024) return file;
                try {
                    const bitmap = await createImageBitmap(file);
                    const maxDim = 1600;
                    const ratio = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
                    const w = Math.round(bitmap.width * ratio);
                    const h = Math.round(bitmap.height * ratio);
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
                    const blob = await new Promise(res => canvas.toBlob(b => res(b), 'image/jpeg', 0.82));
                    if (!blob) return file;
                    return new File([blob], file.name.replace(/\.(png|webp|gif|bmp)$/i, '.jpg'), {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });
                } catch (e) {
                    return file;
                }
            }

            function renderPreview(targetId, files) {
                const box = document.getElementById(targetId);
                box.innerHTML = '';
                if (files.length === 0) {
                    box.classList.add('hidden');
                    return;
                }
                box.classList.remove('hidden');

                files.forEach((f, i) => {
                    const item = document.createElement('div');
                    item.className =
                        'flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-[12px] text-slate-700';
                    if (f.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(f);
                        img.className = 'w-8 h-8 rounded object-cover';
                        item.appendChild(img);
                    } else {
                        const ic = document.createElement('i');
                        ic.className = 'fa-solid fa-paperclip';
                        item.appendChild(ic);
                    }
                    const name = document.createElement('span');
                    name.className = 'truncate max-w-[140px]';
                    name.textContent = f.name;
                    item.appendChild(name);

                    const rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'text-slate-400 hover:text-rose-500';
                    rm.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    rm.onclick = () => {
                        const arr = targetId.includes('reply') ? tenantSelected.reply : tenantSelected.new;
                        arr.splice(i, 1);
                        renderPreview(targetId, arr);
                    };
                    item.appendChild(rm);
                    box.appendChild(item);
                });
            }

            async function handleFilePick(fileList, target) {
                const files = Array.from(fileList || []);
                if (files.length === 0) return;
                const bucket = target === 'reply' ? tenantSelected.reply : tenantSelected.new;
                const max = 5 - bucket.length;
                if (max <= 0) {
                    alert('You can attach at most 5 files per message.');
                    return;
                }
                const picked = files.slice(0, max);
                picked.forEach(f => bucket.push(f));
                const previewId = target === 'reply' ? 'tenant-reply-attachments-preview' :
                    'tenant-new-attachments-preview';
                renderPreview(previewId, bucket);
                for (let i = 0; i < picked.length; i++) {
                    const original = picked[i];
                    const smaller = await compressImage(original);
                    if (smaller !== original) {
                        const idx = bucket.indexOf(original);
                        if (idx > -1) bucket[idx] = smaller;
                    }
                }
                renderPreview(previewId, bucket);
            }

            document.getElementById('tenant-reply-attach-btn').onclick = () =>
                document.getElementById('tenant-reply-files').click();
            document.getElementById('tenant-new-attach-btn').onclick = () =>
                document.getElementById('tenant-new-files').click();
            document.getElementById('tenant-reply-files').onchange = (e) => {
                handleFilePick(e.target.files, 'reply');
                e.target.value = '';
            };
            document.getElementById('tenant-new-files').onchange = (e) => {
                handleFilePick(e.target.files, 'new');
                e.target.value = '';
            };

            document.getElementById('new-message-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('tenant-send-btn');
                const label = document.getElementById('tenant-send-label');
                const subject = document.getElementById('new-subject').value;
                const body = document.getElementById('new-body').value;
                const files = tenantSelected.new;
                if (!subject.trim()) {
                    alert('Please enter a subject.');
                    return;
                }
                if (!body.trim() && files.length === 0) {
                    alert('Please add a message or an attachment.');
                    return;
                }
                btn.disabled = true;
                label.innerText = files.length ? 'Uploading…' : 'Sending…';
                const fd = new FormData();
                fd.append('subject', subject);
                fd.append('body', body);
                files.forEach(f => fd.append('attachments[]', f));
                try {
                    const res = await fetch('/tenants/messages', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf()
                        },
                        body: fd,
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Failed to send.');
                    tenantSelected.new = [];
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
                const files = tenantSelected.reply;
                if ((!body && files.length === 0) || !currentConvId) return;
                const btn = document.getElementById('reply-send-btn');
                const label = document.getElementById('reply-send-label');
                btn.disabled = true;
                if (label) label.innerText = files.length ? 'Uploading…' : 'Sending…';
                const fd = new FormData();
                fd.append('body', body);
                files.forEach(f => fd.append('attachments[]', f));
                try {
                    const res = await fetch(`/tenants/messages/${currentConvId}/reply`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf()
                        },
                        body: fd,
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Failed to send.');
                    ta.value = '';
                    tenantSelected.reply = [];
                    renderPreview('tenant-reply-attachments-preview', []);
                    const item = document.querySelector(
                        `.conversation-item[data-conv-id="${currentConvId}"]`);
                    openThread(currentConvId, item);
                } catch (err) {
                    alert(err.message);
                } finally {
                    btn.disabled = false;
                    if (label) label.innerText = 'Send';
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
                    const matchesTab = tab === 'all' || (tab === 'unread' && isUnread) || (tab === 'read' && !
                        isUnread);
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
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                })[s]);
            }

            let lb, lbImg, lbDownload, lbPrev, lbNext, lbCounter;
            let lbGallery = [];
            let lbIndex = 0;

            function ensureLightbox() {
                if (lb) return;
                lb = document.createElement('div');
                lb.id = 'fb-lightbox';
                lb.style.cssText = `
                    position: fixed; inset: 0; z-index: 9999;
                    background: rgba(10, 12, 16, 0.92);
                    display: none; align-items: center; justify-content: center;
                    opacity: 0; transition: opacity .18s ease;
                    padding: 16px; cursor: zoom-out;
                    -webkit-user-select: none; user-select: none;
                `;
                lb.innerHTML = `
                    <button type="button" id="fb-lb-close" aria-label="Close"
                        style="position:absolute;top:14px;right:18px;background:transparent;border:0;
                               color:#fff;font-size:24px;cursor:pointer;padding:6px;line-height:1;
                               border-radius:8px;transition:background .15s;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <button type="button" id="fb-lb-prev" aria-label="Previous"
                        style="position:absolute;left:14px;top:50%;transform:translateY(-50%);
                               background:rgba(255,255,255,.1);border:0;color:#fff;font-size:22px;
                               cursor:pointer;padding:10px 14px;border-radius:999px;transition:background .15s;">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" id="fb-lb-next" aria-label="Next"
                        style="position:absolute;right:14px;top:50%;transform:translateY(-50%);
                               background:rgba(255,255,255,.1);border:0;color:#fff;font-size:22px;
                               cursor:pointer;padding:10px 14px;border-radius:999px;transition:background .15s;">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <img id="fb-lb-img" src="" alt=""
                         style="max-width:92vw;max-height:88vh;border-radius:12px;
                                box-shadow:0 20px 60px rgba(0,0,0,.6);
                                transform:scale(.9);transition:transform .18s ease;cursor:default;">
                    <div id="fb-lb-counter"
                         style="position:absolute;bottom:20px;left:50%;transform:translateX(-50%);
                                color:#fff;font-size:12px;font-weight:600;opacity:.85;
                                background:rgba(255,255,255,.1);padding:4px 10px;border-radius:999px;display:none;">
                    </div>
                    <a id="fb-lb-download" href="#" download
                       style="position:absolute;bottom:20px;right:20px;background:#fff;color:#0f172a;
                              padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;
                              text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                `;
                document.body.appendChild(lb);

                lbImg = document.getElementById('fb-lb-img');
                lbDownload = document.getElementById('fb-lb-download');
                lbPrev = document.getElementById('fb-lb-prev');
                lbNext = document.getElementById('fb-lb-next');
                lbCounter = document.getElementById('fb-lb-counter');

                lb.addEventListener('click', (e) => {
                    if (e.target === lb) closeLightbox();
                });
                document.getElementById('fb-lb-close').addEventListener('click', closeLightbox);

                lbPrev.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showPrev();
                });
                lbNext.addEventListener('click', (e) => {
                    e.stopPropagation();
                    showNext();
                });

                document.addEventListener('keydown', (e) => {
                    if (!lb || lb.style.display !== 'flex') return;
                    if (e.key === 'Escape') closeLightbox();
                    if (e.key === 'ArrowLeft') showPrev();
                    if (e.key === 'ArrowRight') showNext();
                });
            }

            function openLightbox(gallery, index) {
                ensureLightbox();
                lbGallery = gallery;
                lbIndex = index;

                applyCurrentImage();

                lb.style.display = 'flex';
                requestAnimationFrame(() => {
                    lb.style.opacity = '1';
                    lbImg.style.transform = 'scale(1)';
                });

                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                if (!lb) return;
                lb.style.opacity = '0';
                lbImg.style.transform = 'scale(.9)';
                setTimeout(() => {
                    lb.style.display = 'none';
                    document.body.style.overflow = '';
                }, 180);
            }

            function showPrev() {
                if (lbGallery.length <= 1) return;
                lbIndex = (lbIndex - 1 + lbGallery.length) % lbGallery.length;
                applyCurrentImage();
            }

            function showNext() {
                if (lbGallery.length <= 1) return;
                lbIndex = (lbIndex + 1) % lbGallery.length;
                applyCurrentImage();
            }

            function applyCurrentImage() {
                const current = lbGallery[lbIndex];
                if (!current) return;

                lbImg.style.opacity = '0';
                setTimeout(() => {
                    lbImg.src = current.url;
                    lbImg.alt = current.name || '';
                    lbDownload.href = current.url;
                    lbDownload.setAttribute('download', current.name || 'image');
                    lbImg.style.opacity = '1';
                }, 90);

                if (lbGallery.length > 1) {
                    lbCounter.style.display = 'inline-block';
                    lbCounter.textContent = `${lbIndex + 1} / ${lbGallery.length}`;
                    lbPrev.style.display = 'inline-flex';
                    lbNext.style.display = 'inline-flex';
                } else {
                    lbCounter.style.display = 'none';
                    lbPrev.style.display = 'none';
                    lbNext.style.display = 'none';
                }
            }

            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('.fb-attach-thumb');
                if (!trigger) return;
                e.preventDefault();
                e.stopPropagation();

                const parentBubble = trigger.closest('.bubble-wrap') || document;
                const allThumbs = Array.from(parentBubble.querySelectorAll('.fb-attach-thumb'));
                const gallery = allThumbs.map(t => ({
                    url: t.dataset.src,
                    name: t.dataset.name,
                }));
                const idx = allThumbs.indexOf(trigger);
                openLightbox(gallery, Math.max(0, idx));
            });

            // ============================================================
            // REAL-TIME UNREAD COUNTER UPDATE FUNCTION
            // ============================================================
            function updateTenantGlobalUnreadCount() {
                const unreadRows = document.querySelectorAll('.conversation-item[data-unread="true"]');
                let totalUnread = 0;

                unreadRows.forEach(row => {
                    const badge = row.querySelector('.badge-unread');
                    if (badge) {
                        totalUnread += parseInt(badge.innerText.replace(/\D/g, '')) || 0;
                    }
                });

                const counterEl = document.getElementById('unreadCountText');
                if (counterEl) {
                    if (totalUnread > 0) {
                        counterEl.className = 'text-[#e06800] font-bold';
                        counterEl.innerText = `${totalUnread} unread message(s)`;
                    } else {
                        counterEl.className = 'text-slate-400';
                        counterEl.innerText = `0 unread message(s)`;
                    }
                }
            }

            // ============================================================
            // ECHO — Tenant realtime
            // ============================================================
            function initTenantMessageListener() {
                if (!window.Echo) {
                    setTimeout(initTenantMessageListener, 300);
                    return;
                }
                try {
                    const tenantId = {{ Auth::user()->tenant?->id ?? 'null' }};
                    const userId = {{ Auth::id() }};
                    const channel = tenantId ? `tenant.${tenantId}.messages` : `user.${userId}.messages`;

                    window.Echo.private(channel)
                        .listen('.message.sent', (payload) => {
                            if (currentConvId && String(payload.conversation_id) === String(currentConvId)) {
                                const item = document.querySelector(
                                    `.conversation-item[data-conv-id="${currentConvId}"]`);
                                openThread(currentConvId, item);
                                return;
                            }
                            let listItem = document.querySelector(
                                `.conversation-item[data-conv-id="${payload.conversation_id}"]`);
                            if (listItem) {
                                const previewEl = listItem.querySelector('.line-clamp-2');
                                if (previewEl) {
                                    previewEl.innerText = payload.sender_role === 'tenant' ?
                                        `You: ${payload.body || '📎 Attachment'}` :
                                        (payload.body || '📎 Attachment');
                                }
                                const timeEl = listItem.querySelector('.text-xs.text-slate-400');
                                if (timeEl) {
                                    timeEl.innerText = new Date(payload.ts).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    });
                                }
                                if (payload.sender_role === 'admin') {
                                    listItem.dataset.unread = 'true';
                                    listItem.classList.remove('bg-white');
                                    listItem.classList.add('bg-amber-50');
                                    const iconBox = listItem.querySelector('.w-11');
                                    if (iconBox) {
                                        iconBox.classList.remove('bg-slate-100', 'text-[#041d40]');
                                        iconBox.classList.add('bg-amber-100', 'text-amber-600');
                                    }

                                    // 1. FIND EXISTING BADGE & GET COUNT
                                    let currentUnread = 0;
                                    const existingBadge = listItem.querySelector('.badge-unread');
                                    if (existingBadge) {
                                        currentUnread = parseInt(existingBadge.innerText.replace(/\D/g, '')) || 0;
                                    }

                                    // 2. REMOVE OLD BADGE
                                    if (existingBadge) existingBadge.remove();

                                    // 3. CALCULATE NEW COUNT
                                    const newUnread = currentUnread + 1;

                                    // 4. APPEND NEW BADGE TO BOTTOM ROW
                                    const bottomRow = listItem.querySelector('.flex.justify-between.items-center');
                                    if (bottomRow) {
                                        // Remove any leftover badges in the bottom row just in case
                                        bottomRow.querySelectorAll('.badge-unread').forEach(b => b.remove());

                                        bottomRow.innerHTML = `
                                            <span class="text-xs text-slate-400">Building Management</span>
                                            <span class="badge-unread bg-[#e06800] text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full">${newUnread} new</span>
                                        `;
                                    }
                                }

                                const container = document.getElementById('conversationsContainer');
                                if (container.contains(listItem)) {
                                    listItem.remove();
                                }
                                container.prepend(listItem);

                                // UPDATE GLOBAL COUNTER
                                updateTenantGlobalUnreadCount();

                            } else if (!currentConvId) {
                                window.location.reload();
                            }
                        });
                } catch (e) {
                    console.error('Echo tenant.messages bind failed:', e);
                }
            }

            initTenantMessageListener();
        })();
    </script>
@endpush
