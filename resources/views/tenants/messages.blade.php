@extends('layouts.tenant', ['active' => 'messages', 'pageTitle' => 'Messages'])
@section('title', 'Messages')

@section('content')
<div class="p-4 md:p-8 max-w-5xl mx-auto" id="messages-root">

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

    <div id="messages-list-view">
        <div class="flex justify-between items-center text-[13px] mb-4">
            <span class="text-slate-500">4 conversation(s)</span>
            <span class="text-[#e06800] font-bold">1 unread message(s)</span>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-7 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" placeholder="Search messages..."
                       class="w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-lg text-[13px] outline-none">
            </div>

            @foreach([
                ['subject' => 'Upcoming Building Maintenance', 'date' => 'Apr 30', 'body' => 'Dear Elle, this is to inform you that we will be conducting routine building maintenance on May 15, 2026 from 9:00 AM to 3:00 PM...', 'unread' => true],
                ['subject' => 'Request for Payment Extension', 'date' => 'Apr 29', 'body' => 'Hello Elle, I hope your family member recovers soon. We understand that emergencies happen. Your request for a 3-day payment extension has been approved...', 'unread' => false],
                ['subject' => 'Parking Space Issue', 'date' => 'Apr 27', 'body' => 'Hi Elle, thank you for bringing this to our attention. We apologize for the inconvenience. Our security team has been notified...', 'unread' => false],
                ['subject' => 'Question About Rooftop Access', 'date' => 'Apr 22', 'body' => 'Hello Elle Yes, all tenants have access to the rooftop area. Operating hours are from 6:00 AM to 10:00 PM daily...', 'unread' => false],
            ] as $msg)
            <div class="p-5 border-b border-slate-100 last:border-0 flex gap-4 {{ $msg['unread'] ? 'bg-yellow-50' : '' }}">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center flex-shrink-0 {{ $msg['unread'] ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-[#041d40]' }} text-lg">
                    <i class="fa-regular fa-envelope"></i>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-baseline mb-1.5">
                        <span class="text-sm font-bold text-slate-900">{{ $msg['subject'] }}</span>
                        <span class="text-xs text-slate-400">{{ $msg['date'] }}</span>
                    </div>
                    <div class="text-[13px] text-slate-600 leading-relaxed mb-3">{{ $msg['body'] }}</div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-slate-400">Admin</span>
                        @if($msg['unread'])
                            <span class="bg-[#e06800] text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full">1 new</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- New message form (hidden, toggled by JS) --}}
    <div id="messages-form-view" class="hidden">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <button id="messages-back-btn" class="text-slate-700 text-base"><i class="fa-solid fa-arrow-left"></i></button>
                <h4 class="text-base font-bold text-slate-900">New Message</h4>
            </div>
            <form class="p-6 flex flex-col gap-5" onsubmit="event.preventDefault();">
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">To</label>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-[13px] text-slate-600">Building Management</div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">Subject *</label>
                    <input type="text" class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none" placeholder="Enter message subject...">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[13px] font-semibold text-slate-700">Message *</label>
                    <textarea class="w-full border border-slate-200 rounded-lg px-4 py-3 text-[13px] outline-none resize-none min-h-[180px]" placeholder="Type your message..."></textarea>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <button type="button" id="messages-cancel-btn" class="text-slate-600 text-sm font-semibold px-4 py-2.5">Cancel</button>
                    <button type="submit" class="bg-[#041d40] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2">
                        <i class="fa-regular fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const list = document.getElementById('messages-list-view');
        const form = document.getElementById('messages-form-view');
        document.getElementById('new-message-btn').onclick = () => { list.classList.add('hidden'); form.classList.remove('hidden'); };
        document.getElementById('messages-back-btn').onclick = () => { list.classList.remove('hidden'); form.classList.add('hidden'); };
        document.getElementById('messages-cancel-btn').onclick = () => { list.classList.remove('hidden'); form.classList.add('hidden'); };
    })();
</script>
@endpush