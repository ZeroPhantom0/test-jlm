@extends('layouts.tenant', ['active' => 'settings', 'pageTitle' => 'Settings'])
@section('title', 'Settings')

@section('content')
<div class="p-4 md:p-8 max-w-6xl mx-auto">

    <div class="flex items-center gap-4 mb-7">
        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0">
            <i class="fa-solid fa-gear"></i>
        </div>
        <div class="min-w-0">
            <h2 class="text-xl font-extrabold text-slate-900">Account Settings</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Configure building management preferences</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start" id="settings-root">

        {{-- Tabs --}}
        <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 shadow-sm">
            <button type="button"
                    class="settings-tab active flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold transition-all bg-[#02122c] text-white"
                    data-tab="profile">
                <div class="flex items-center gap-3">
                    <i class="fa-regular fa-user"></i>
                    <span>Profile</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="security">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-lock"></i>
                    <span>Security</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="preferences">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-palette"></i>
                    <span>Preferences</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>
        </div>

        {{-- Panels --}}
        <div class="min-w-0">

            {{-- PROFILE --}}
            <div class="settings-panel bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-profile">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Personal Information</h3>
                <p class="text-[13px] text-slate-500 mb-6">Update your profile details</p>

                <div class="flex items-center gap-4 mb-6">
                    <img src="https://i.pravatar.cc/100?img=47" class="w-16 h-16 rounded-full object-cover" alt="Elle">
                    <div>
                        <h4 class="text-[15px] font-bold text-slate-900">Elle Dela Cruz</h4>
                        <div class="text-[12.5px] text-slate-500 mb-1">Tenant</div>
                        <a class="text-emerald-500 text-[12.5px] font-semibold cursor-pointer hover:underline">Change photo</a>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Full Name</label>
                    <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="Elle Dela Cruz">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">Email Address</label>
                        <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="elledelacruz01@gmail.com">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">Phone Number</label>
                        <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="09121409895">
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Role</label>
                    <input readonly class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] text-slate-500 cursor-not-allowed" value="Tenant">
                </div>
                <div class="flex justify-end">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-regular fa-floppy-disk"></i> Save Profile
                    </button>
                </div>
            </div>

            {{-- SECURITY --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-security">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Change Password</h3>
                <p class="text-[13px] text-slate-500 mb-6">Update your account password</p>

                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Current Password</label>
                    <div class="relative">
                        <input type="password" value="123456789012" class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 pr-10 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white">
                        <i class="fa-regular fa-eye absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer"></i>
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">New Password</label>
                    <input type="password" placeholder="Min. 8 characters" class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white">
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Confirm New Password</label>
                    <input type="password" placeholder="Repeat new password" class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white">
                </div>
                <div class="flex justify-end">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </div>
            </div>

            {{-- PREFERENCES --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-preferences">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Preferences</h3>
                <p class="text-[13px] text-slate-500 mb-6">Customize your boarding house experience</p>

                @foreach([
                    ['id' => 'email', 'label' => 'Email Notifications', 'default' => true],
                    ['id' => 'sms', 'label' => 'SMS Alerts', 'default' => false],
                    ['id' => 'push', 'label' => 'Push Notifications', 'default' => false],
                ] as $pref)
                <div class="flex items-center justify-between py-4 border-b border-slate-50 last:border-0">
                    <div class="flex items-start gap-3">
                        <i class="fa-regular fa-bell text-base text-slate-900 mt-0.5"></i>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $pref['label'] }}</div>
                            <div class="text-xs text-slate-400 mt-0.5" id="{{ $pref['id'] }}-status">{{ $pref['default'] ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                    </div>
                    <label class="relative inline-block w-11 h-6 cursor-pointer">
                        <input type="checkbox" id="{{ $pref['id'] }}-toggle" @if($pref['default']) checked @endif class="peer opacity-0 w-0 h-0">
                        <span class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500"></span>
                        <span class="absolute left-[3px] bottom-[3px] bg-white w-[18px] h-[18px] rounded-full transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
                @endforeach

                <div class="flex justify-end mt-7">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-regular fa-floppy-disk"></i> Save Appearance
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');

    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(t => {
            t.classList.remove('bg-[#02122c]', 'text-white');
            t.classList.add('text-slate-600', 'hover:bg-slate-50');
            const ch = t.querySelector('.chevron');
            if (ch) ch.classList.add('hidden');
        });

        tab.classList.add('bg-[#02122c]', 'text-white');
        tab.classList.remove('text-slate-600', 'hover:bg-slate-50');
        const activeCh = tab.querySelector('.chevron');
        if (activeCh) activeCh.classList.remove('hidden');

        panels.forEach(p => p.classList.add('hidden'));
        document.getElementById('panel-' + tab.dataset.tab).classList.remove('hidden');
    }));

    ['email','sms','push'].forEach(id => {
        const t = document.getElementById(id + '-toggle');
        const s = document.getElementById(id + '-status');
        if (t) t.addEventListener('change', () => s.textContent = t.checked ? 'Enabled' : 'Disabled');
    });
})();
</script>
@endpush