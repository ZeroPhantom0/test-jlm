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

    {{-- Conversations List View --}}
    <div id="admin-messages-list-view" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-5 py-4 flex items-center gap-2.5 border-b border-slate-100">
            <button class="tab-btn active bg-[#041d40] text-white px-4 py-2 rounded-[10px] text-[13px] font-semibold">All Messages</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50">Unread</button>
            <button class="tab-btn text-slate-600 px-4 py-2 rounded-[10px] text-[13px] font-semibold hover:bg-slate-50">Read</button>
        </div>

        <div class="px-5 py-4 border-b border-slate-100">
            <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" placeholder="Search by tenant name or subject..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
        </div>

        @foreach([
            ['tenant' => 'Elle Dela Cruz', 'date' => 'Apr 29, 01:28 PM', 'subject' => 'Request for Payment Extension', 'preview' => 'Hello Elle, I hope your family member recovers soon. We understand that emergencies happen. Your request for a 3-day payment extension has been approved...', 'count' => 3],
            ['tenant' => 'Mau Santos', 'date' => 'Apr 28, 01:28 PM', 'subject' => 'Noise Complaint', 'preview' => 'Hello Mau, thank you for informing us. We take noise complaints seriously as we want all our tenants to be comfortable...', 'count' => 2],
            ['tenant' => 'Elle Dela Cruz', 'date' => 'Apr 27, 01:28 PM', 'subject' => 'Parking Space Issue', 'preview' => 'Hi Elle, thank you for bringing this to our attention. We apologize for the inconvenience. Our security team has been notified...', 'count' => 2],
            ['tenant' => 'Elle Dela Cruz', 'date' => 'Apr 22, 01:28 PM', 'subject' => 'Question About Rooftop Access', 'preview' => 'Hello Elle! Yes, all tenants have access to the rooftop area. Operating hours are from 6:00 AM to 10:00 PM daily...', 'count' => 2],
        ] as $msg)
        <div class="px-5 py-4 border-b border-slate-50 last:border-0 flex gap-4 cursor-pointer hover:bg-slate-50 transition">
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

    {{-- New Message Form View (hidden) --}}
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
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const list = document.getElementById('admin-messages-list-view');
        const form = document.getElementById('admin-messages-new-view');
        document.getElementById('admin-new-message-btn').onclick = () => { list.classList.add('hidden'); form.classList.remove('hidden'); };
        document.getElementById('admin-messages-back-btn').onclick = () => { list.classList.remove('hidden'); form.classList.add('hidden'); };
        document.getElementById('admin-messages-cancel-btn').onclick = () => { list.classList.remove('hidden'); form.classList.add('hidden'); };
    })();
</script>
@endpush