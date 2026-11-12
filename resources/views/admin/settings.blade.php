@extends('layouts.admin', ['active' => 'settings', 'pageTitle' => 'Settings'])
@section('title', 'Settings')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">
        <div id="admin-settings-modals"></div>

        {{-- Page Header --}}
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-11 h-11 shrink-0 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-gear"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-slate-900 truncate">System Settings</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Configure building management preferences</p>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-[13px] rounded-lg px-4 py-3 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="bg-rose-50 border border-rose-200 text-rose-800 text-[13px] rounded-lg px-4 py-3 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="bg-rose-50 border border-rose-200 text-rose-800 text-[13px] rounded-lg px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">

            {{-- Left Nav Tabs --}}
            <div id="settings-tabs"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 lg:p-3
                        flex flex-row lg:flex-col gap-1
                        overflow-x-auto lg:overflow-visible
                        lg:sticky lg:top-20
                        scrollbar-thin">
                <button data-tab="profile"
                    class="settings-tab active whitespace-nowrap shrink-0 flex items-center justify-between gap-2 px-3.5 py-2.5 lg:py-3 rounded-lg text-[13px] lg:text-[13.5px] font-semibold bg-[#02122b] text-white">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-regular fa-user text-sm"></i>
                        <span>Profile</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-[11px] hidden lg:inline"></i>
                </button>

                <button data-tab="security"
                    class="settings-tab whitespace-nowrap shrink-0 flex items-center justify-between gap-2 px-3.5 py-2.5 lg:py-3 rounded-lg text-[13px] lg:text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-lock text-sm"></i>
                        <span>Security</span>
                    </div>
                </button>

                <button data-tab="preferences"
                    class="settings-tab whitespace-nowrap shrink-0 flex items-center justify-between gap-2 px-3.5 py-2.5 lg:py-3 rounded-lg text-[13px] lg:text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-palette text-sm"></i>
                        <span>Preferences</span>
                    </div>
                </button>

                <button data-tab="data"
                    class="settings-tab whitespace-nowrap shrink-0 flex items-center justify-between gap-2 px-3.5 py-2.5 lg:py-3 rounded-lg text-[13px] lg:text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-database text-sm"></i>
                        <span>Data Management</span>
                    </div>
                </button>
            </div>

            {{-- Right Panels --}}
            <div class="min-w-0 space-y-6">

                {{-- PROFILE (Merged with General) --}}
                <div class="settings-panel" data-panel="profile">
                    <form action="{{ route('admin.settings.profile') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-6">
                            <h3 class="text-base font-bold text-slate-900 mb-1">Personal Information</h3>
                            <p class="text-[12.5px] text-slate-500 mb-5">Update your profile details</p>

                            @php
                                $avatarSrc = $personalInfo?->profile_picture
                                    ? (str_starts_with($personalInfo->profile_picture, 'http')
                                        ? $personalInfo->profile_picture
                                        : asset('storage/' . $personalInfo->profile_picture))
                                    : null;
                            @endphp

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
                                @if ($avatarSrc)
                                    <img id="profile-preview" src="{{ $avatarSrc }}" alt="Avatar"
                                        class="w-16 h-16 rounded-full object-cover border border-slate-200 shrink-0">
                                @else
                                    <div id="profile-preview-fallback"
                                        class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center border border-slate-200 text-2xl shrink-0">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <img id="profile-preview" src=""
                                        class="w-16 h-16 rounded-full object-cover border border-slate-200 hidden"
                                        alt="Avatar">
                                @endif

                                <div class="min-w-0">
                                    <h4 class="text-[15px] font-bold text-slate-900 truncate">{{ $user->name }}</h4>
                                    <p class="text-xs text-slate-500 mb-1">Admin</p>
                                    <label for="profile-photo-input"
                                        class="text-emerald-500 text-xs font-semibold cursor-pointer hover:underline">
                                        Change photo
                                    </label>
                                    <input type="file" id="profile-photo-input" name="profile_picture" accept="image/*"
                                        class="hidden">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                    class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-[12.5px] font-medium text-slate-800">Email Address</label>
                                        <button type="button"
                                            class="text-[11px] font-bold text-emerald-600 hover:underline"
                                            onclick="openAdminSettingsModal('email')">Change</button>
                                    </div>
                                    <input readonly value="{{ $user->email }}"
                                        class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-[12.5px] font-medium text-slate-800">Phone Number</label>
                                        <button type="button"
                                            class="text-[11px] font-bold text-emerald-600 hover:underline"
                                            onclick="openAdminSettingsModal('phone')">Change</button>
                                    </div>
                                    <input readonly value="{{ $user->phone ?? 'Not set' }}"
                                        class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Role</label>
                                <input type="text" value="Admin" disabled
                                    class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed">
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                            <h3 class="text-base font-bold text-slate-900 mb-1">Building Information</h3>
                            <p class="text-[12.5px] text-slate-500 mb-5">Basic information about your property</p>

                            <div class="mb-4">
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Building Name</label>
                                <input type="text" value="JLM Cozy Cabin" disabled
                                    class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed text-slate-500">
                            </div>

                            <div class="mb-4">
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Address</label>
                                <input type="text" value="1517 M.H. Del Pilar St., Ermita, Manila" disabled
                                    class="w-full border border-slate-200 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed text-slate-500">
                            </div>

                            <div class="flex justify-end mt-5">
                                <button type="submit"
                                    class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                                    <i class="fa-regular fa-floppy-disk"></i> Save Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- SECURITY --}}
                <div class="settings-panel hidden" data-panel="security">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Security Settings</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Manage your account password</p>

                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div>
                                    <div class="text-[14px] font-bold text-slate-900">Password</div>
                                    <div class="text-[12px] text-slate-500">Update your password securely</div>
                                </div>
                            </div>
                            <button type="button" onclick="openAdminSettingsModal('password')"
                                class="bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-[13px] font-semibold transition">
                                Change Password
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Security Options</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Manage advanced security features</p>

                        <div class="mb-4 md:w-1/2">
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Session Timeout</label>
                            <select
                                class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 bg-white">
                                <option>30 minutes</option>
                                <option>1 hour</option>
                                <option>2 hours</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- PREFERENCES --}}
                <div class="settings-panel hidden" data-panel="preferences">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Preferences</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Customize your boarding house experience</p>

                        @foreach ([['id' => 'email-notif', 'label' => 'Email Notifications', 'desc' => 'Receive updates via email', 'icon' => 'fa-regular fa-envelope', 'default' => true], ['id' => 'sms-alerts', 'label' => 'SMS Alerts', 'desc' => 'Get text message updates', 'icon' => 'fa-regular fa-comment-dots', 'default' => false]] as $pref)
                            <div
                                class="flex items-center justify-between gap-3 py-3 border-b border-slate-100 last:border-b-0">
                                <div class="flex items-start gap-3 min-w-0">
                                    <i class="{{ $pref['icon'] }} text-[15px] text-slate-900 mt-0.5 shrink-0"></i>
                                    <div class="min-w-0">
                                        <div class="text-[13.5px] font-semibold text-slate-900">{{ $pref['label'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $pref['desc'] }}</div>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center w-11 h-6 cursor-pointer shrink-0">
                                    <input type="checkbox" @if ($pref['default']) checked @endif
                                        class="peer sr-only">
                                    <span
                                        class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500"></span>
                                    <span
                                        class="absolute left-[2px] top-[2px] bg-white w-5 h-5 rounded-full shadow transition-transform peer-checked:translate-x-5"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- DATA MANAGEMENT --}}
                <div class="settings-panel hidden" data-panel="data">
                    {{-- ... [Keep the entire Data Management panel exactly as it is in your current file] ... --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Backup Database</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Create and manage database snapshots to protect your
                            data</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
                            <form action="{{ route('admin.settings.backup') }}" method="POST" class="block">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-[#02122b] text-white rounded-[10px] px-4 py-4 flex items-center gap-3.5 hover:bg-[#092042] transition text-left">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-base shrink-0">
                                        <i class="fa-solid fa-hard-drive"></i>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[13.5px] font-bold">Backup Now</span>
                                        <span class="text-[11.5px] text-slate-400">Manual snapshot</span>
                                    </div>
                                </button>
                            </form>

                            <div
                                class="bg-amber-50 border border-amber-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                                <div
                                    class="w-10 h-10 rounded-lg bg-yellow-100 text-amber-700 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-regular fa-circle-check"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[13.5px] font-bold text-amber-900">Last Backup</span>
                                    <span class="text-[11.5px] text-amber-700 truncate">
                                        {{ count($backups) > 0 ? $backups[0]['date'] . ' ' . $backups[0]['time'] : 'No backups yet' }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5 sm:col-span-2 lg:col-span-1">
                                <div
                                    class="w-10 h-10 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-solid fa-database"></i>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[13.5px] font-bold text-slate-900">Total Backups</span>
                                    <span class="text-[11.5px] text-slate-500">{{ count($backups) }} snapshots</span>
                                </div>
                            </div>
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 mb-3">Recent Backups</h4>
                        <div class="overflow-x-auto -mx-5 sm:mx-0 px-5 sm:px-0">
                            <table class="w-full min-w-[520px] border-collapse">
                                <thead>
                                    <tr
                                        class="text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                                        <th class="text-left py-2.5 px-3">File</th>
                                        <th class="text-left py-2.5 px-3">Date</th>
                                        <th class="text-left py-2.5 px-3">Size</th>
                                        <th class="text-left py-2.5 px-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody data-paginate>
                                    @forelse($backups as $b)
                                        <tr class="border-b border-slate-100 last:border-b-0">
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-2.5 font-semibold text-[12.5px]">
                                                    <i class="fa-solid fa-hard-drive text-slate-400"></i>
                                                    <div class="break-all">{{ $b['name'] }}</div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-[12.5px] whitespace-nowrap">
                                                {{ $b['date'] }}<br>
                                                <span class="text-[11px] text-slate-500">{{ $b['time'] }}</span>
                                            </td>
                                            <td class="py-3 px-3 text-[12.5px] whitespace-nowrap">{{ $b['size'] }}</td>
                                            <td class="py-3 px-3">
                                                <a href="{{ route('admin.settings.download-backup', $b['name']) }}"
                                                    class="text-emerald-500 text-[12px] font-semibold inline-flex items-center gap-1 whitespace-nowrap">
                                                    <i class="fa-solid fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-6 text-center text-slate-400 text-[13px]">
                                                No backups created yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Export System Data</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Download records for reporting, compliance, or
                            migration</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-5">
                            <label
                                class="export-format-card selected border-2 border-emerald-500 bg-emerald-50 rounded-[10px] p-4 text-center cursor-pointer transition"
                                data-format="csv">
                                <input type="radio" name="export_format" value="csv" checked class="hidden">
                                <i class="fa-solid fa-file-csv text-[20px] text-emerald-500 mb-1.5 block"></i>
                                <span class="block text-[13px] font-bold text-emerald-500">CSV</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Spreadsheet friendly</span>
                            </label>

                            <label
                                class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300"
                                data-format="xlsx">
                                <input type="radio" name="export_format" value="xlsx" class="hidden">
                                <i class="fa-solid fa-file-excel text-[20px] text-slate-400 mb-1.5 block"></i>
                                <span class="block text-[13px] font-bold text-slate-900">Excel (.xlsx)</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Formatted workbook</span>
                            </label>

                            <label
                                class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300"
                                data-format="json">
                                <input type="radio" name="export_format" value="json" class="hidden">
                                <i class="fa-solid fa-code text-[20px] text-slate-400 mb-1.5 block"></i>
                                <span class="block text-[13px] font-bold text-slate-900">JSON</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Raw structured data</span>
                            </label>
                        </div>

                        <h4 class="text-[13.5px] font-bold text-slate-900 mb-3">Select Modules to Export</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <a href="{{ route('admin.settings.export', ['module' => 'tenants', 'format' => 'csv']) }}"
                                class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left"
                                data-module="tenants" data-base-url="{{ route('admin.settings.export') }}" download>
                                <i class="fa-solid fa-users text-[14px] text-slate-500 mb-1 block"></i>
                                <div class="text-[12px] font-semibold text-slate-900">Tenants</div>
                                <div class="text-[11px] text-slate-500">{{ $stats['tenants'] }} records</div>
                            </a>

                            <a href="{{ route('admin.settings.export', ['module' => 'rooms', 'format' => 'csv']) }}"
                                class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left"
                                data-module="rooms" data-base-url="{{ route('admin.settings.export') }}" download>
                                <i class="fa-solid fa-bed text-[14px] text-slate-500 mb-1 block"></i>
                                <div class="text-[12px] font-semibold text-slate-900">Rooms</div>
                                <div class="text-[11px] text-slate-500">{{ $stats['rooms'] }} profiles</div>
                            </a>

                            <a href="{{ route('admin.settings.export', ['module' => 'billing', 'format' => 'csv']) }}"
                                class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left"
                                data-module="billing" data-base-url="{{ route('admin.settings.export') }}" download>
                                <i class="fa-solid fa-credit-card text-[14px] text-slate-500 mb-1 block"></i>
                                <div class="text-[12px] font-semibold text-slate-900">Billing</div>
                                <div class="text-[11px] text-slate-500">{{ $stats['billing'] }} invoices</div>
                            </a>

                            <a href="{{ route('admin.settings.export', ['module' => 'maintenance', 'format' => 'csv']) }}"
                                class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-400 block text-left"
                                data-module="maintenance" data-base-url="{{ route('admin.settings.export') }}" download>
                                <i class="fa-solid fa-wrench text-[14px] text-slate-500 mb-1 block"></i>
                                <div class="text-[12px] font-semibold text-slate-900">Maintenance</div>
                                <div class="text-[11px] text-slate-500">{{ $stats['maintenance'] }} requests</div>
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                        <h3 class="text-base font-bold text-slate-900 mb-1">Archive Records</h3>
                        <p class="text-[12.5px] text-slate-500 mb-5">Move old, inactive records to long-term cold storage
                            to keep the database lean</p>

                        <div class="bg-amber-50 border border-amber-200 rounded-[10px] p-4 flex gap-3.5 mb-6">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5 shrink-0"></i>
                            <div class="text-[13px] text-amber-900 leading-relaxed">
                                Archived records are moved to read-only cold storage. They remain accessible in History but
                                no longer appear in active views.
                            </div>
                        </div>

                        <form action="{{ route('admin.settings.archive') }}" method="POST">
                            @csrf
                            <div class="flex flex-col gap-3">
                                @foreach ([['key' => 'maintenance', 'title' => 'Resolved Maintenance Requests (>6 months)', 'count' => $archiveCandidates['maintenance']], ['key' => 'reservations', 'title' => 'Completed Reservations (>1 year)', 'count' => $archiveCandidates['reservations']], ['key' => 'billing', 'title' => 'Closed Billing Records (>2 years)', 'count' => $archiveCandidates['billing']], ['key' => 'announcements', 'title' => 'Expired Announcements (>90 days)', 'count' => $archiveCandidates['announcements']]] as $arc)
                                    @php $isEmpty = $arc['count'] === 0; @endphp
                                    <label
                                        class="archive-item flex items-center justify-between gap-3 px-4 sm:px-5 py-4 rounded-xl bg-white border-[1.5px] border-slate-200 transition {{ $isEmpty ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:border-slate-300' }}">
                                        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                                            <input type="checkbox" name="types[]" value="{{ $arc['key'] }}"
                                                data-count="{{ $arc['count'] }}"
                                                class="archive-checkbox w-5 h-5 rounded-[5px] border-2 border-slate-300 text-emerald-500 focus:ring-emerald-500 shrink-0"
                                                @if ($isEmpty) disabled @endif>
                                            <div class="min-w-0">
                                                <div
                                                    class="archive-title text-sm font-semibold text-slate-900 break-words">
                                                    {{ $arc['title'] }}</div>
                                                <div class="archive-sub text-[12.5px] text-slate-500">{{ $arc['count'] }}
                                                    records @if ($isEmpty)
                                                        <span class="text-slate-400">• nothing to archive</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-6 pt-4 border-t border-slate-200">
                                <div class="text-[13px] text-slate-500">
                                    <span class="font-semibold text-slate-900" id="archive-count">0</span> records
                                    selected for archiving
                                </div>
                                <button type="submit" id="archive-submit-btn"
                                    class="bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg px-5 py-2.5 text-[13px] font-semibold inline-flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto"
                                    disabled>
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
        window.openAdminSettingsModal = function(action) {
            window.dispatchEvent(new CustomEvent('admin-settings:open-modal', {
                detail: {
                    action
                }
            }));
        };

        (function() {
            // 1. TAB SWITCHING
            const tabs = document.querySelectorAll('.settings-tab');
            const panels = document.querySelectorAll('.settings-panel');

            function activateTab(tab) {
                tabs.forEach(t => {
                    t.classList.remove('active', 'bg-[#02122b]', 'text-white');
                    t.classList.add('text-slate-600', 'hover:bg-slate-50');
                    t.querySelectorAll('.fa-chevron-right').forEach(c => c.remove());
                });

                tab.classList.add('active', 'bg-[#02122b]', 'text-white');
                tab.classList.remove('text-slate-600', 'hover:bg-slate-50');

                const arrow = document.createElement('i');
                arrow.className = 'fa-solid fa-chevron-right text-[11px] hidden lg:inline';
                tab.appendChild(arrow);

                panels.forEach(p => p.classList.add('hidden'));
                const target = document.querySelector(`.settings-panel[data-panel="${tab.dataset.tab}"]`);
                if (target) target.classList.remove('hidden');
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => activateTab(tab));
            });

            // 2. RESTORE ACTIVE TAB
            (function restoreTab() {
                const hash = window.location.hash.replace(/^#/, '');
                let wanted = null;
                if (hash.startsWith('tab=')) {
                    wanted = hash.slice(4);
                } else if (hash && document.querySelector(`.settings-tab[data-tab="${hash}"]`)) {
                    wanted = hash;
                }
                if (wanted) {
                    const tab = document.querySelector(`.settings-tab[data-tab="${wanted}"]`);
                    if (tab) activateTab(tab);
                }
            })();

            // 3. CHANGE PHOTO PREVIEW
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

            // 4. EXPORT FORMAT SELECTION
            const formatCards = document.querySelectorAll('.export-format-card');
            let selectedFormat = 'csv';

            formatCards.forEach(card => {
                card.addEventListener('click', () => {
                    formatCards.forEach(c => {
                        c.classList.remove('selected', 'border-2', 'border-emerald-500',
                            'bg-emerald-50');
                        c.classList.add('border', 'border-slate-200', 'bg-white');
                        const icon = c.querySelector('i');
                        const title = c.querySelector('span:first-of-type');
                        if (icon) {
                            icon.classList.remove('text-emerald-500');
                            icon.classList.add('text-slate-400');
                        }
                        if (title) {
                            title.classList.remove('text-emerald-500');
                            title.classList.add('text-slate-900');
                        }
                    });

                    card.classList.add('selected', 'border-2', 'border-emerald-500', 'bg-emerald-50');
                    card.classList.remove('border', 'border-slate-200', 'bg-white');
                    const icon = card.querySelector('i');
                    const title = card.querySelector('span:first-of-type');
                    if (icon) {
                        icon.classList.remove('text-slate-400');
                        icon.classList.add('text-emerald-500');
                    }
                    if (title) {
                        title.classList.remove('text-slate-900');
                        title.classList.add('text-emerald-500');
                    }

                    selectedFormat = card.dataset.format;
                    updateExportLinks();
                });
            });

            function updateExportLinks() {
                document.querySelectorAll('.module-export-box').forEach(link => {
                    const module = link.dataset.module;
                    const base = link.dataset.baseUrl;
                    if (module && base) {
                        link.href =
                            `${base}?module=${encodeURIComponent(module)}&format=${encodeURIComponent(selectedFormat)}`;
                    }
                });
            }
            updateExportLinks();

            // 5. ARCHIVE SELECTION COUNTER
            const archiveItems = document.querySelectorAll('.archive-item');
            const archiveCountEl = document.getElementById('archive-count');
            const archiveSubmitBtn = document.getElementById('archive-submit-btn');

            function updateArchiveCount() {
                let total = 0;
                document.querySelectorAll('.archive-item input[type="checkbox"]:checked').forEach(cb => {
                    total += parseInt(cb.dataset.count || '0', 10);
                });
                if (archiveCountEl) archiveCountEl.textContent = total.toLocaleString();
                if (archiveSubmitBtn) archiveSubmitBtn.disabled = total === 0;
            }

            archiveItems.forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                if (checkbox && !checkbox.disabled) {
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

            updateArchiveCount();
        })();
    </script>
@endpush
