@extends('layouts.admin', ['active' => 'settings', 'pageTitle' => 'Settings'])
@section('title', 'Settings')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-gear"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">System Settings</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Configure building management preferences</p>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-[13px] rounded-lg px-4 py-3 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 text-[13px] rounded-lg px-4 py-3 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">

        {{-- Left Nav Tabs --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 flex flex-col gap-1 sticky top-4" id="settings-tabs">
            <button data-tab="general" class="settings-tab active flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold bg-[#02122b] text-white">
                <div class="flex items-center gap-2.5">
                    <i class="fa-regular fa-building text-sm"></i>
                    <span>General</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px]"></i>
            </button>

            <button data-tab="profile" class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                <div class="flex items-center gap-2.5">
                    <i class="fa-regular fa-user text-sm"></i>
                    <span>Profile</span>
                </div>
            </button>

            <button data-tab="security" class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-lock text-sm"></i>
                    <span>Security</span>
                </div>
            </button>

            <button data-tab="preferences" class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-palette text-sm"></i>
                    <span>Preferences</span>
                </div>
            </button>

            <button data-tab="data" class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-database text-sm"></i>
                    <span>Data Management</span>
                </div>
            </button>
        </div>

        {{-- Right Panels --}}
        <div class="min-w-0 space-y-6">

            {{-- GENERAL --}}
            <div class="settings-panel" data-panel="general">
                <form action="{{ route('admin.settings.general') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Owner / Manager Information</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Contact details of the property owner and manager</p>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Owner / Manager Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Contact Number</label>
                                <div class="relative">
                                    <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Official Email</label>
                                <div class="relative">
                                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Building Information</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Basic information about your property</p>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Building Name</label>
                            <input type="text" value="JLM Cozy Cabin" disabled class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed text-slate-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Address</label>
                            <input type="text" value="1517 M.H. Del Pilar St., Ermita, Manila" disabled class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed text-slate-500">
                        </div>

                        <div class="flex justify-end mt-5">
                            <button type="submit" class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                                <i class="fa-regular fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- PROFILE --}}
            <div class="settings-panel hidden" data-panel="profile">
                <form action="{{ route('admin.settings.profile') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Personal Information</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Update your profile details</p>

                        @php
                            $avatarSrc = $personalInfo?->profile_picture 
                                ? (str_starts_with($personalInfo->profile_picture, 'http') 
                                    ? $personalInfo->profile_picture 
                                    : asset('storage/' . $personalInfo->profile_picture))
                                : null;
                        @endphp

                        <div class="flex items-center gap-4 mb-6">
                            @if($avatarSrc)
                                <img id="profile-preview" src="{{ $avatarSrc }}" alt="Avatar" class="w-16 h-16 rounded-full object-cover border border-slate-200">
                            @else
                                <div id="profile-preview-fallback" class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center border border-slate-200 text-2xl">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <img id="profile-preview" src="" class="w-16 h-16 rounded-full object-cover border border-slate-200 hidden" alt="Avatar">
                            @endif
                            
                            <div>
                                <h4 class="text-[15px] font-bold text-slate-900">{{ $user->name }}</h4>
                                <p class="text-xs text-slate-500 mb-1">Admin</p>
                                <label for="profile-photo-input" class="text-emerald-500 text-xs font-semibold cursor-pointer hover:underline">Change photo</label>
                                <input type="file" id="profile-photo-input" name="profile_picture" accept="image/*" class="hidden">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Role</label>
                            <input type="text" value="Admin" disabled class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                                <i class="fa-regular fa-floppy-disk"></i> Save Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- SECURITY --}}
            <div class="settings-panel hidden" data-panel="security">
                <form action="{{ route('admin.settings.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Change Password</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Update your account password</p>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Current Password</label>
                            <div class="relative">
                                <input type="password" name="current_password" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 pr-10 text-[13px] outline-none focus:border-slate-400">
                                <i class="fa-regular fa-eye absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px] cursor-pointer password-toggle"></i>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">New Password</label>
                            <input type="password" name="password" placeholder="Min. 8 characters" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>

                        <div class="mb-4">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Confirm New Password</label>
                            <input type="password" name="password_confirmation" placeholder="Repeat new password" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                                <i class="fa-solid fa-lock"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Security Options</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Manage advanced security features</p>

                    <div class="mb-4 md:w-1/2">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Session Timeout</label>
                        <select class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 bg-white">
                            <option>30 minutes</option>
                            <option>1 hour</option>
                            <option>2 hours</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- PREFERENCES --}}
            <div class="settings-panel hidden" data-panel="preferences">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Preferences</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Customize your boarding house experience</p>

                    @foreach([
                        ['id' => 'email-notif', 'label' => 'Email Notifications', 'default' => true],
                        ['id' => 'push-notif', 'label' => 'Push Notifications', 'default' => false],
                        ['id' => 'dark-mode', 'label' => 'Dark Mode', 'default' => false],
                    ] as $pref)
                    <div class="flex items-center justify-between py-3 border-b border-slate-100 last:border-b-0">
                        <div class="flex items-start gap-3">
                            <i class="fa-regular fa-bell text-[15px] text-slate-900 mt-0.5"></i>
                            <div>
                                <div class="text-[13.5px] font-semibold text-slate-900">{{ $pref['label'] }}</div>
                                <div class="text-xs text-slate-500">{{ $pref['default'] ? 'Enabled' : 'Disabled' }}</div>
                            </div>
                        </div>
                        <label class="relative inline-block w-11 h-6 cursor-pointer">
                            <input type="checkbox" @if($pref['default']) checked @endif class="peer opacity-0 w-0 h-0">
                            <span class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500"></span>
                            <span class="absolute left-0.75 bottom-0.75 bg-white w-4.5 h-4.5 rounded-full transition peer-checked:translate-x-5"></span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- DATA MANAGEMENT --}}
            <div class="settings-panel hidden" data-panel="data">

                {{-- ============ BACKUP DATABASE CARD ============ --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Backup Database</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Create and manage database snapshots to protect your data</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <form action="{{ route('admin.settings.backup') }}" method="POST" class="block">
                            @csrf
                            <button type="submit" class="w-full bg-[#02122b] text-white rounded-[10px] px-4 py-4 flex items-center gap-3.5 hover:bg-[#092042] transition text-left">
                                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-base">
                                    <i class="fa-solid fa-hard-drive"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[13.5px] font-bold">Backup Now</span>
                                    <span class="text-[11.5px] text-slate-400">Manual snapshot</span>
                                </div>
                            </button>
                        </form>

                        <div class="bg-amber-50 border border-amber-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-yellow-100 text-amber-700 flex items-center justify-center text-base">
                                <i class="fa-regular fa-circle-check"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13.5px] font-bold text-amber-900">Last Backup</span>
                                <span class="text-[11.5px] text-amber-700">
                                    {{ count($backups) > 0 ? $backups[0]['date'] . ' ' . $backups[0]['time'] : 'No backups yet' }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center text-base">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13.5px] font-bold text-slate-900">Total Backups</span>
                                <span class="text-[11.5px] text-slate-500">{{ count($backups) }} snapshots</span>
                            </div>
                        </div>
                    </div>

                    {{-- Automatic Backups Toggle --}}
                    <div class="border border-slate-200 rounded-lg p-5 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[13.5px] font-bold text-slate-900">Automatic Backups</div>
                                <div class="text-xs text-slate-500">Schedule regular database snapshots</div>
                            </div>
                            <label class="relative inline-block w-11 h-6 cursor-pointer">
                                <input type="checkbox" checked disabled class="peer opacity-0 w-0 h-0">
                                <span class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500 opacity-60"></span>
                                <span class="absolute left-0.75 bottom-0.75 bg-white w-4.5 h-4.5 rounded-full transition peer-checked:translate-x-5"></span>
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">*Requires server cron configuration (coming soon)</p>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 mb-3">Recent Backups</h4>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                                <th class="text-left py-2.5 px-3">File</th>
                                <th class="text-left py-2.5 px-3">Date</th>
                                <th class="text-left py-2.5 px-3">Size</th>
                                <th class="text-left py-2.5 px-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $b)
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2.5 font-semibold text-[12.5px]">
                                        <i class="fa-solid fa-hard-drive text-slate-400"></i>
                                        <div>{{ $b['name'] }}</div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-[12.5px]">{{ $b['date'] }}<br><span class="text-[11px] text-slate-500">{{ $b['time'] }}</span></td>
                                <td class="py-3 px-3 text-[12.5px]">{{ $b['size'] }}</td>
                                <td class="py-3 px-3">
                                    <a href="{{ route('admin.settings.download-backup', $b['name']) }}" class="text-emerald-500 text-[12px] font-semibold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 text-[13px]">No backups created yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ============ EXPORT SYSTEM DATA CARD ============ --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Export System Data</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Download records for reporting, compliance, or migration</p>

                    {{-- Export Format Options --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <label class="export-format-card selected border-2 border-emerald-500 bg-emerald-50 rounded-[10px] p-4 text-center cursor-pointer transition" data-format="csv">
                            <input type="radio" name="export_format" value="csv" checked class="hidden">
                            <i class="fa-solid fa-file-csv text-[20px] text-emerald-500 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-emerald-500">CSV</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Spreadsheet friendly</span>
                        </label>

                        <label class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300" data-format="xlsx">
                            <input type="radio" name="export_format" value="xlsx" class="hidden">
                            <i class="fa-solid fa-file-excel text-[20px] text-slate-400 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-slate-900">Excel (.xlsx)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Formatted workbook</span>
                        </label>

                        <label class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300" data-format="json">
                            <input type="radio" name="export_format" value="json" class="hidden">
                            <i class="fa-solid fa-code text-[20px] text-slate-400 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-slate-900">JSON</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Raw structured data</span>
                        </label>
                    </div>

                    <h4 class="text-[13.5px] font-bold text-slate-900 mb-3">Select Modules to Export</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <a href="#" class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left" data-module="tenants">
                            <i class="fa-solid fa-users text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Tenants</div>
                            <div class="text-[11px] text-slate-500">{{ $stats['tenants'] }} records</div>
                        </a>

                        <a href="#" class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left" data-module="rooms">
                            <i class="fa-solid fa-bed text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Rooms</div>
                            <div class="text-[11px] text-slate-500">{{ $stats['rooms'] }} profiles</div>
                        </a>

                        <a href="#" class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left" data-module="billing">
                            <i class="fa-solid fa-credit-card text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Billing</div>
                            <div class="text-[11px] text-slate-500">{{ $stats['billing'] }} invoices</div>
                        </a>

                        <a href="#" class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left" data-module="maintenance">
                            <i class="fa-solid fa-wrench text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Maintenance</div>
                            <div class="text-[11px] text-slate-500">{{ $stats['maintenance'] }} requests</div>
                        </a>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2"><i class="fa-solid fa-circle-info mr-1"></i> Select a format above, then click a module to download.</p>
                </div>

                {{-- ============ ARCHIVE RECORDS CARD ============ --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Archive Records</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Move old, inactive records to long-term cold storage to keep the database lean</p>

                    <div class="bg-amber-50 border border-amber-200 rounded-[10px] p-4 flex gap-3.5 mb-6">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5"></i>
                        <div class="text-[13px] text-amber-900 leading-relaxed">
                            Archived records are moved to read-only cold storage. They remain accessible in History but no longer appear in active views. This action can be reversed by an administrator.
                        </div>
                    </div>

                    <form action="{{ route('admin.settings.archive') }}" method="POST">
                        @csrf
                        <div class="flex flex-col gap-3">
                            @foreach([
                                ['key' => 'maintenance', 'title' => 'Resolved Maintenance Requests (>6 months)', 'sub' => '84 records • 1.2 MB'],
                                ['key' => 'reservations', 'title' => 'Completed Reservations (>1 year)', 'sub' => '127 records • 0.8 MB'],
                                ['key' => 'billing', 'title' => 'Closed Billing Records (>2 years)', 'sub' => '312 records • 2.1 MB'],
                                ['key' => 'announcements', 'title' => 'Expired Announcements (>90 days)', 'sub' => '43 records • 0.3 MB'],
                            ] as $arc)
                            <label class="archive-item flex items-center justify-between px-5 py-4 rounded-xl bg-white border-[1.5px] border-slate-200 cursor-pointer transition hover:border-slate-300">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" name="types[]" value="{{ $arc['key'] }}" class="archive-checkbox w-5 h-5 rounded-[5px] border-2 border-slate-300 text-emerald-500 focus:ring-emerald-500">
                                    <div>
                                        <div class="archive-title text-sm font-semibold text-slate-900">{{ $arc['title'] }}</div>
                                        <div class="archive-sub text-[12.5px] text-slate-500">{{ $arc['sub'] }}</div>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>

                        <div class="flex justify-between items-center mt-6 pt-4 border-t border-slate-200">
                            <div class="text-[13px] text-slate-500">
                                <span class="font-semibold text-slate-900" id="archive-count">0</span> records selected for archiving
                            </div>
                            <button type="submit" class="bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg px-5 py-2.5 text-[13px] font-semibold inline-flex items-center gap-2 transition">
                                <i class="fa-solid fa-box-archive"></i> Archive Selected
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ============================================================
    // 1. TAB SWITCHING
    // ============================================================
    const tabs = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');

    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(t => {
            t.classList.remove('active','bg-[#02122b]','text-white');
            t.classList.add('text-slate-600','hover:bg-slate-50');
            const ch = t.querySelector('.fa-chevron-right');
            if (ch) ch.remove();
        });
        tab.classList.add('active','bg-[#02122b]','text-white');
        tab.classList.remove('text-slate-600','hover:bg-slate-50');

        const arrow = document.createElement('i');
        arrow.className = 'fa-solid fa-chevron-right text-[11px]';
        tab.appendChild(arrow);

        panels.forEach(p => p.classList.add('hidden'));
        const target = document.querySelector(`.settings-panel[data-panel="${tab.dataset.tab}"]`);
        if (target) target.classList.remove('hidden');
    }));

    // ============================================================
    // 2. PASSWORD VISIBILITY TOGGLES
    // ============================================================
    document.querySelectorAll('.password-toggle').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = icon.closest('.relative').querySelector('input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });
    });

    // ============================================================
    // 3. CHANGE PHOTO — trigger file input & live preview
    // ============================================================
    const photoInput = document.getElementById('profile-photo-input');
    if (photoInput) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (file) {
                const img = document.getElementById('profile-preview');
                const fallback = document.getElementById('profile-preview-fallback');
                if (img) {
                    img.src = URL.createObjectURL(file);
                    img.classList.remove('hidden');
                }
                if (fallback) fallback.classList.add('hidden');
            }
        });
    }

    // ============================================================
    // 4. EXPORT FORMAT SELECTION
    // ============================================================
    const formatCards = document.querySelectorAll('.export-format-card');
    let selectedFormat = 'csv';

    formatCards.forEach(card => {
        card.addEventListener('click', () => {
            // Remove active classes from all
            formatCards.forEach(c => {
                c.classList.remove('selected', 'border-2', 'border-emerald-500', 'bg-emerald-50');
                c.classList.add('border', 'border-slate-200', 'bg-white');

                const icon = c.querySelector('i');
                const title = c.querySelector('span:first-of-type');
                if (icon) { icon.classList.remove('text-emerald-500'); icon.classList.add('text-slate-400'); }
                if (title) { title.classList.remove('text-emerald-500'); title.classList.add('text-slate-900'); }
            });

            // Add active classes to clicked
            card.classList.add('selected', 'border-2', 'border-emerald-500', 'bg-emerald-50');
            card.classList.remove('border', 'border-slate-200', 'bg-white');

            const icon = card.querySelector('i');
            const title = card.querySelector('span:first-of-type');
            if (icon) { icon.classList.remove('text-slate-400'); icon.classList.add('text-emerald-500'); }
            if (title) { title.classList.remove('text-slate-900'); title.classList.add('text-emerald-500'); }

            selectedFormat = card.dataset.format;
            updateExportLinks();
        });
    });

    function updateExportLinks() {
        document.querySelectorAll('.module-export-box').forEach(link => {
            const module = link.dataset.module;
            if (module) {
                // Build the URL with both module and format parameters
                link.href = `/admin/settings/export?module=${encodeURIComponent(module)}&format=${encodeURIComponent(selectedFormat)}`;
            }
        });
    }
    // Initial call to set links
    updateExportLinks();

    // ============================================================
    // 5. ARCHIVE SELECTION COUNTER
    // ============================================================
    const archiveItems = document.querySelectorAll('.archive-item');
    const archiveCountEl = document.getElementById('archive-count');

    function updateArchiveCount() {
        let total = 0;
        document.querySelectorAll('.archive-item input[type="checkbox"]:checked').forEach(cb => {
            const sub = cb.closest('.archive-item').querySelector('.archive-sub').textContent;
            const m = sub.match(/\d+/);
            if (m) total += parseInt(m[0]);
        });
        if (archiveCountEl) archiveCountEl.textContent = total;
    }

    archiveItems.forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    item.classList.add('border-emerald-500', 'bg-emerald-50');
                } else {
                    item.classList.remove('border-emerald-500', 'bg-emerald-50');
                }
                updateArchiveCount();
            });
        }
    });
})();
</script>
@endpush