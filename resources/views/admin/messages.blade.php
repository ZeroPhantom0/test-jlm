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
        <span class="text-slate-500" id="admin-msg-count">Showing 4 of 4 conversation(s)</span>
        <span>0 unread message(s)</span>
    </div>

    {{-- ============================================ --}}
    {{-- VIEW 1: Conversations List                    --}}
    {{-- ============================================ --}}
    <div id="admin-messages-list-view" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-5 py-4 flex items-center gap-2.5 border-b border-slate-100">
            <button class="tab-btn active bg-[#041d40] text-white px-4 py-2 rounded-[10px] text-[13px] font-semibold" data-tab="all">All Messages</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50" data-tab="unread">Unread</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50" data-tab="read">Read</button>
        </div>

        <div class="px-5 py-4 border-b border-slate-100">
            <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" placeholder="Search by tenant name or subject..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
        </div>

        {{-- Conversation Items --}}
        <div id="admin-conversations-container" class="flex flex-col">
            @foreach([
                ['key' => 'elle1', 'tenant' => 'Elle Dela Cruz', 'date' => 'Apr 29, 01:28 PM', 'subject' => 'Request for Payment Extension', 'preview' => 'Hello Elle, I hope your family member recovers soon. We understand that emergencies happen. Your request for a 3-day payment extension has been approved...', 'count' => 3],
                ['key' => 'mau',   'tenant' => 'Mau Santos',     'date' => 'Apr 28, 01:28 PM', 'subject' => 'Noise Complaint',                 'preview' => 'Hello Mau, thank you for informing us. We take noise complaints seriously as we want all our tenants to be comfortable...', 'count' => 2],
                ['key' => 'elle2', 'tenant' => 'Elle Dela Cruz', 'date' => 'Apr 27, 01:28 PM', 'subject' => 'Parking Space Issue',             'preview' => 'Hi Elle, thank you for bringing this to our attention. We apologize for the inconvenience. Our security team has been notified...', 'count' => 2],
                ['key' => 'elle3', 'tenant' => 'Elle Dela Cruz', 'date' => 'Apr 22, 01:28 PM', 'subject' => 'Question About Rooftop Access',   'preview' => 'Hello Elle! Yes, all tenants have access to the rooftop area. Operating hours are from 6:00 AM to 10:00 PM daily...', 'count' => 2],
            ] as $msg)
            <div class="admin-conversation-item px-5 py-4 border-b border-slate-50 last:border-0 flex gap-4 cursor-pointer hover:bg-slate-50 transition"
                 data-thread-key="{{ $msg['key'] }}">
                <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-[16px] shrink-0">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-sm font-bold text-slate-900">{{ $msg['tenant'] }}</div>
                        <div class="text-[11px] text-slate-400">{{ $msg['date'] }}</div>
                    </div>
                    <div class="text-xs text-slate-500 mb-1.5">{{ $msg['subject'] }}</div>
                    <div class="text-[12.5px] text-slate-600 leading-relaxed mb-2 line-clamp-2">
                        <span class="text-[#f97316] font-bold">You:</span> {{ $msg['preview'] }}
                    </div>
                    <div class="text-[11px] text-slate-400">{{ $msg['count'] }} message(s)</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Empty state for Unread filter --}}
        <div id="admin-empty-unread" class="hidden flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-3xl mb-4">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <h4 class="text-[15px] font-bold text-slate-900 mb-1">No messages found</h4>
            <p class="text-[13px] text-slate-500">All messages have been read</p>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- VIEW 2: New Message Form                      --}}
    {{-- ============================================ --}}
    <div id="admin-messages-new-view" class="hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <button id="admin-messages-back-btn" class="text-slate-700 text-base"><i class="fa-solid fa-arrow-left"></i></button>
                <h3 class="text-base font-bold text-slate-900">New Message</h3>
            </div>
            <div class="p-6 flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-700">To</label>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                            <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                            <select class="w-full bg-transparent text-[13px] outline-none border-none">
                                <option>Select Tenant(s)</option>
                                <option>Elle Dela Cruz</option>
                                <option>Mau Santos</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-bold text-slate-700">Mobile Number</label>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                            <i class="fa-solid fa-phone text-slate-400 text-sm"></i>
                            <input type="text" value="09127584582" readOnly class="w-full bg-transparent text-[13px] outline-none border-none">
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-700">Subject *</label>
                    <input type="text" placeholder="Enter message subject..." class="bg-white border border-slate-200 rounded-xl px-4 py-3 text-[13px] outline-none">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-bold text-slate-700">Message *</label>
                    <textarea placeholder="Type your message..." class="bg-white border border-slate-200 rounded-xl px-4 py-3 text-[13px] outline-none resize-y min-h-[180px]"></textarea>
                </div>
            </div>
            <div class="px-5 pb-5 flex justify-between items-center">
                <button id="admin-messages-cancel-btn" class="text-slate-600 text-sm font-semibold px-4 py-2.5">Cancel</button>
                <button class="bg-[#041d40] hover:bg-[#0d2a52] text-white px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                    <i class="fa-regular fa-paper-plane"></i> Send Message
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- VIEW 3: Message Thread                        --}}
    {{-- ============================================ --}}
    <div id="admin-messages-thread-view" class="hidden">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Thread Header --}}
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-start gap-3">
                <button id="admin-thread-back-btn" class="text-slate-700 text-base mt-0.5">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="flex flex-col">
                    <div id="admin-thread-tenant" class="text-base font-bold text-slate-900">Elle Dela Cruz</div>
                    <div id="admin-thread-subject" class="text-xs text-slate-400 mt-0.5">Request for Payment Extension</div>
                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-regular fa-clock"></i>
                        <span id="admin-thread-count">3 message(s) in this conversation</span>
                    </div>
                </div>
            </div>

            {{-- Messages Body (populated by JS) --}}
            <div id="admin-thread-messages" class="p-6 flex flex-col gap-4">
                {{-- JS will inject bubbles here --}}
            </div>

            {{-- Reply Box --}}
            <div class="px-5 pb-5 pt-2 flex items-center gap-4">
                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
                    <textarea placeholder="Type your reply..."
                              class="w-full bg-transparent border-none outline-none text-[13px] resize-none h-12 text-slate-900"></textarea>
                </div>
                <button class="bg-[#02122b] hover:bg-[#0d2347] text-white rounded-xl px-6 py-3 text-sm font-semibold inline-flex items-center gap-2 h-[72px] transition">
                    <i class="fa-regular fa-paper-plane"></i> Send
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ============================================================
    // VIEW SWITCHING
    // ============================================================
    const listView   = document.getElementById('admin-messages-list-view');
    const formView   = document.getElementById('admin-messages-new-view');
    const threadView = document.getElementById('admin-messages-thread-view');

    function showListView() {
        listView.classList.remove('hidden');
        formView.classList.add('hidden');
        threadView.classList.add('hidden');
    }

    function showFormView() {
        listView.classList.add('hidden');
        formView.classList.remove('hidden');
        threadView.classList.add('hidden');
    }

    function showThreadView() {
        listView.classList.add('hidden');
        formView.classList.add('hidden');
        threadView.classList.remove('hidden');
    }

    // ============================================================
    // New Message button + back/cancel
    // ============================================================
    document.getElementById('admin-new-message-btn').onclick    = showFormView;
    document.getElementById('admin-messages-back-btn').onclick  = showListView;
    document.getElementById('admin-messages-cancel-btn').onclick= showListView;
    document.getElementById('admin-thread-back-btn').onclick    = showListView;

    // ============================================================
    // THREAD DATA
    // ============================================================
    const threadData = {
        elle1: {
            name: 'Elle Dela Cruz',
            subject: 'Request for Payment Extension',
            countText: '3 message(s) in this conversation',
            messages: [
                {
                    sender: 'Elle Dela Cruz',
                    time: 'Apr 28, 2026 · 01:28 PM',
                    text: 'Good morning! I hope this message finds you well. I would like to request a payment extension for my rent due to an unexpected medical emergency in my family. I can provide documentation if needed. Would it be possible to extend the payment deadline by 3 days? Thank you for your understanding.',
                    isAdmin: false,
                },
                {
                    sender: 'Leah Administrator',
                    time: 'Apr 29, 2026 · 01:28 PM',
                    text: "Hello Elle, I hope your family member recovers soon. We understand that emergencies happen. Your request for a 3-day payment extension has been approved. Your new payment deadline is May 10, 2026. Please ensure payment is made by then to avoid late fees. If you need any further assistance, don't hesitate to reach out. Take care!",
                    isAdmin: true,
                },
                {
                    sender: 'Elle Dela Cruz',
                    time: 'Apr 29, 2026 · 01:28 PM',
                    text: 'Thank you so much for your understanding and quick response! I really appreciate the extension. I will make sure to settle the payment before May 10. Have a great day!',
                    isAdmin: false,
                },
            ],
        },
        mau: {
            name: 'Mau Santos',
            subject: 'Noise Complaint',
            countText: '2 message(s) in this conversation',
            messages: [
                {
                    sender: 'Mau Santos',
                    time: 'Apr 27, 2026 · 01:28 PM',
                    text: 'Hi, I wanted to report excessive noise coming from the unit above mine (Room 202) late at night, around 11 PM - 1 AM. This has been happening for the past week. Could you please remind the tenant about the quiet hours policy? Thank you.',
                    isAdmin: false,
                },
                {
                    sender: 'Leah Administrator',
                    time: 'Apr 28, 2026 · 01:28 PM',
                    text: 'Hello Mau, thank you for informing us. We take noise complaints seriously as we want all our tenants to be comfortable. I have sent a formal reminder to the tenant in Room 202 about our quiet hours policy (10 PM - 7 AM). If the issue persists, please let us know immediately and we will take further action. We appreciate your patience.',
                    isAdmin: true,
                },
            ],
        },
        elle2: {
            name: 'Elle Dela Cruz',
            subject: 'Parking Space Issue',
            countText: '2 message(s) in this conversation',
            messages: [
                {
                    sender: 'Elle Dela Cruz',
                    time: 'Apr 26, 2026 · 01:28 PM',
                    text: 'Hi, someone keeps parking in my assigned slot #4.',
                    isAdmin: false,
                },
                {
                    sender: 'Leah Administrator',
                    time: 'Apr 27, 2026 · 01:28 PM',
                    text: 'Hi Elle, thank you for bringing this to our attention. We apologize for the inconvenience. Our security team has been notified and they will monitor the parking area more closely.',
                    isAdmin: true,
                },
            ],
        },
        elle3: {
            name: 'Elle Dela Cruz',
            subject: 'Question About Rooftop Access',
            countText: '2 message(s) in this conversation',
            messages: [
                {
                    sender: 'Elle Dela Cruz',
                    time: 'Apr 21, 2026 · 01:28 PM',
                    text: 'Hi, what are the operating hours for the rooftop garden?',
                    isAdmin: false,
                },
                {
                    sender: 'Leah Administrator',
                    time: 'Apr 22, 2026 · 01:28 PM',
                    text: 'Hello Elle! Yes, all tenants have access to the rooftop area. Operating hours are from 6:00 AM to 10:00 PM daily.',
                    isAdmin: true,
                },
            ],
        },
    };

    // ============================================================
    // OPEN THREAD
    // ============================================================
    function openThread(key) {
        const data = threadData[key];
        if (!data) return;

        document.getElementById('admin-thread-tenant').innerText  = data.name;
        document.getElementById('admin-thread-subject').innerText = data.subject;
        document.getElementById('admin-thread-count').innerText   = data.countText;

        const container = document.getElementById('admin-thread-messages');
        container.innerHTML = '';

        data.messages.forEach((msg) => {
            const bubble = document.createElement('div');

            if (msg.isAdmin) {
                bubble.className = 'rounded-2xl p-4 text-[13px] leading-relaxed bg-[#f97316] text-white';
            } else {
                bubble.className = 'rounded-2xl p-4 text-[13px] leading-relaxed bg-slate-100 text-slate-800';
            }

            const checkMark = msg.isAdmin
                ? '<i class="fa-solid fa-check text-[10px] ml-1"></i>'
                : '';

            bubble.innerHTML = `
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold">${msg.sender}</span>
                    <span class="text-[11px] opacity-80">${msg.time}${checkMark}</span>
                </div>
                <div>${msg.text}</div>
            `;

            container.appendChild(bubble);
        });

        showThreadView();

        // Scroll to bottom of thread
        container.scrollTop = container.scrollHeight;
    }

    // ============================================================
    // ATTACH CLICK HANDLERS TO CONVERSATION ITEMS
    // ============================================================
    document.querySelectorAll('.admin-conversation-item').forEach((item) => {
        item.addEventListener('click', () => {
            openThread(item.dataset.threadKey);
        });
    });

    // ============================================================
    // TAB FILTER (All / Unread / Read)
    // ============================================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const conversationsContainer = document.getElementById('admin-conversations-container');
    const emptyUnread = document.getElementById('admin-empty-unread');
    const showingCountText = document.getElementById('admin-msg-count');

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            // reset active state on all tabs
            tabBtns.forEach((b) => {
                b.classList.remove('active', 'bg-[#041d40]', 'text-white');
                b.classList.add('text-slate-600', 'hover:bg-slate-50');
            });
            btn.classList.add('active', 'bg-[#041d40]', 'text-white');
            btn.classList.remove('text-slate-600', 'hover:bg-slate-50');

            const tab = btn.dataset.tab;

            if (tab === 'unread') {
                conversationsContainer.classList.add('hidden');
                emptyUnread.classList.remove('hidden');
                emptyUnread.classList.add('flex');
                showingCountText.innerText = 'Showing 0 of 4 conversation(s)';
            } else {
                conversationsContainer.classList.remove('hidden');
                emptyUnread.classList.add('hidden');
                emptyUnread.classList.remove('flex');
                showingCountText.innerText = 'Showing 4 of 4 conversation(s)';
            }
        });
    });
})();
</script>
@endpush