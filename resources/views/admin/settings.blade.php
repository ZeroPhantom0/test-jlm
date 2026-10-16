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
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Owner / Manager Information</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Contact details of the property owner and manager</p>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Owner / Manager Name</label>
                        <input type="text" value="Leah Agujar" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Owner Contact Number</label>
                            <div class="relative">
                                <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                <input type="text" value="09171234567" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Official Email</label>
                            <div class="relative">
                                <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                <input type="email" value="jlmcozycabin@gmail.com" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Building Information</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Basic information about your property</p>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Building Name</label>
                        <input type="text" value="JLM Cozy Cabin" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Address</label>
                        <input type="text" value="1517 M.H. Del Pilar St., Ermita, Manila, Manila, Philippines" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Operating Hours</label>
                            <div class="relative">
                                <i class="fa-regular fa-clock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                <input type="text" value="24/7" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Facebook Page</label>
                            <div class="relative">
                                <i class="fa-brands fa-facebook absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]"></i>
                                <input type="text" value="fb.com/jlmcozycabin" class="w-full border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-5">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-regular fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>

            {{-- PROFILE --}}
            <div class="settings-panel hidden" data-panel="profile">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Personal Information</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Update your profile details</p>

                    <div class="flex items-center gap-4 mb-6">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=150&auto=format&fit=crop" alt="Avatar" class="w-16 h-16 rounded-full object-cover">
                        <div>
                            <h4 class="text-[15px] font-bold text-slate-900">Leah Administrator</h4>
                            <p class="text-xs text-slate-500 mb-1">Admin</p>
                            <a href="#" class="text-emerald-500 text-xs font-semibold hover:underline">Change photo</a>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Full Name</label>
                        <input type="text" value="Leah Administrator" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Email Address</label>
                            <input type="email" value="admin@jlm.com" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Phone Number</label>
                            <input type="text" value="0916 223 9873" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Role</label>
                        <input type="text" value="Admin" disabled class="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3.5 py-2.5 text-[13px] outline-none cursor-not-allowed">
                    </div>

                    <div class="flex justify-end">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-regular fa-floppy-disk"></i> Save Profile
                        </button>
                    </div>
                </div>
            </div>

            {{-- SECURITY --}}
            <div class="settings-panel hidden" data-panel="security">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Change Password</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Update your account password</p>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Current Password</label>
                        <div class="relative">
                            <input type="password" value="password123" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 pr-10 text-[13px] outline-none focus:border-slate-400">
                            <i class="fa-regular fa-eye absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[13px] cursor-pointer"></i>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">New Password</label>
                        <input type="password" placeholder="Min. 8 characters" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Confirm New Password</label>
                        <input type="password" placeholder="Repeat new password" class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div class="flex justify-end">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-solid fa-lock"></i> Update Password
                        </button>
                    </div>
                </div>

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

                    <div class="flex justify-end">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-regular fa-floppy-disk"></i> Save Security Settings
                        </button>
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

                    <div class="flex items-center justify-between py-3 mt-2 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-globe text-[15px] text-slate-900"></i>
                            <span class="text-[13.5px] font-semibold text-slate-900">Language</span>
                        </div>
                        <select class="border border-slate-200 rounded-lg px-3 py-2 text-[13px] outline-none bg-white">
                            <option>English</option>
                            <option>Filipino</option>
                        </select>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-regular fa-floppy-disk"></i> Save Preference
                        </button>
                    </div>
                </div>
            </div>

            {{-- DATA MANAGEMENT --}}
            <div class="settings-panel hidden" data-panel="data">

                {{-- ============ BACKUP DATABASE CARD ============ --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Backup Database</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Create and manage database snapshots to protect your data</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <div class="bg-[#02122b] text-white rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-base">
                                <i class="fa-solid fa-hard-drive"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13.5px] font-bold">Backup Now</span>
                                <span class="text-[11.5px] text-slate-400">Manual snapshot</span>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-yellow-100 text-amber-700 flex items-center justify-center text-base">
                                <i class="fa-regular fa-circle-check"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13.5px] font-bold text-amber-900">Last Backup</span>
                                <span class="text-[11.5px] text-amber-700">May 6, 2026 14:30</span>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-4 flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center text-base">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[13.5px] font-bold text-slate-900">Total Backups</span>
                                <span class="text-[11.5px] text-slate-500">5 snapshots • 20.0 MB</span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-lg p-5 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="text-[13.5px] font-bold text-slate-900">Automatic Backups</div>
                                <div class="text-xs text-slate-500">Schedule regular database snapshots</div>
                            </div>
                            <label class="relative inline-block w-11 h-6 cursor-pointer">
                                <input type="checkbox" checked class="peer opacity-0 w-0 h-0">
                                <span class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500"></span>
                                <span class="absolute left-0.75 bottom-0.75 bg-white w-4.5 h-4.5 rounded-full transition peer-checked:translate-x-5"></span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Frequency</label>
                                <select class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Daily (2:00 AM)</option>
                                    <option>Weekly</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[12.5px] font-medium text-slate-800 mb-1.5">Retention Period</label>
                                <select class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none bg-white">
                                    <option>30 days</option>
                                    <option>60 days</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 mb-3">Recent Backups</h4>
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                                <th class="text-left py-2.5 px-3">File</th>
                                <th class="text-left py-2.5 px-3">Date</th>
                                <th class="text-left py-2.5 px-3">Size</th>
                                <th class="text-left py-2.5 px-3">Status</th>
                                <th class="text-left py-2.5 px-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['file' => 'backup_20260506_143012', 'type' => 'Manual', 'date' => 'May 6, 2026', 'time' => '14:30', 'size' => '4.2 MB'],
                                ['file' => 'backup_20260505_020000', 'type' => 'Auto', 'date' => 'May 5, 2026', 'time' => '02:00', 'size' => '4.1 MB'],
                                ['file' => 'backup_20260504_020000', 'type' => 'Auto', 'date' => 'May 4, 2026', 'time' => '02:00', 'size' => '4.0 MB'],
                            ] as $b)
                            <tr class="border-b border-slate-100 last:border-b-0">
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2.5 font-semibold text-[12.5px]">
                                        <i class="fa-solid fa-hard-drive text-slate-400"></i>
                                        <div>
                                            <div>{{ $b['file'] }}</div>
                                            <div class="text-[11px] text-slate-500 font-normal">{{ $b['type'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-[12.5px]">{{ $b['date'] }}<br><span class="text-[11px] text-slate-500">{{ $b['time'] }}</span></td>
                                <td class="py-3 px-3 text-[12.5px]">{{ $b['size'] }}</td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center gap-1 text-emerald-600 bg-emerald-50 text-[11px] font-semibold px-2 py-0.5 rounded-xl">
                                        <i class="fa-regular fa-circle-check"></i> Success
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <a href="#" class="text-emerald-500 text-[12px] font-semibold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ============ EXPORT SYSTEM DATA CARD (NEW) ============ --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
                    <h3 class="text-base font-bold text-slate-900 mb-1">Export System Data</h3>
                    <p class="text-[12.5px] text-slate-500 mb-5">Download records for reporting, compliance, or migration</p>

                    {{-- Export Format Options --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <div class="export-format-card selected border-2 border-emerald-500 bg-emerald-50 rounded-[10px] p-4 text-center cursor-pointer transition"
                             data-format="csv">
                            <i class="fa-solid fa-file-csv text-[20px] text-emerald-500 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-emerald-500">CSV</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Spreadsheet friendly</span>
                        </div>

                        <div class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300"
                             data-format="xlsx">
                            <i class="fa-solid fa-file-excel text-[20px] text-slate-400 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-slate-900">Excel (.xlsx)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Formatted workbook</span>
                        </div>

                        <div class="export-format-card border border-slate-200 bg-white rounded-[10px] p-4 text-center cursor-pointer transition hover:border-slate-300"
                             data-format="json">
                            <i class="fa-solid fa-code text-[20px] text-slate-400 mb-1.5 block"></i>
                            <span class="block text-[13px] font-bold text-slate-900">JSON</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Raw structured data</span>
                        </div>
                    </div>

                    {{-- Modules to Export --}}
                    <h4 class="text-[13.5px] font-bold text-slate-900 mb-3">Select Modules to Export</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-300">
                            <i class="fa-solid fa-users text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Tenants</div>
                            <div class="text-[11px] text-slate-500">4 active records</div>
                        </div>

                        <div class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-300">
                            <i class="fa-solid fa-bed text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Rooms</div>
                            <div class="text-[11px] text-slate-500">12 room profiles</div>
                        </div>

                        <div class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-300">
                            <i class="fa-solid fa-credit-card text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Billing</div>
                            <div class="text-[11px] text-slate-500">Payments &amp; Invoices</div>
                        </div>

                        <div class="module-export-box border border-slate-200 bg-white rounded-lg px-3 py-3 cursor-pointer transition hover:border-slate-300">
                            <i class="fa-solid fa-wrench text-[14px] text-slate-500 mb-1 block"></i>
                            <div class="text-[12px] font-semibold text-slate-900">Maintenance</div>
                            <div class="text-[11px] text-slate-500">Service requests</div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button class="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-[#092042] transition">
                            <i class="fa-solid fa-download"></i> Export Data
                        </button>
                    </div>
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

                    <div class="flex flex-col gap-3">
                        @foreach([
                            ['title' => 'Resolved Maintenance Requests (>6 months)', 'sub' => '84 records • 1.2 MB', 'selected' => true],
                            ['title' => 'Completed Reservations (>1 year)', 'sub' => '127 records • 0.8 MB', 'selected' => false],
                            ['title' => 'Closed Billing Records (>2 years)', 'sub' => '312 records • 2.1 MB', 'selected' => false],
                            ['title' => 'Expired Announcements (>90 days)', 'sub' => '43 records • 0.3 MB', 'selected' => false],
                        ] as $arc)
                        <div class="archive-item flex items-center justify-between px-5 py-4 rounded-xl bg-white border-[1.5px] border-slate-200 cursor-pointer transition hover:border-slate-300 {{ $arc['selected'] ? 'selected border-emerald-500 bg-emerald-50' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="archive-checkbox w-5 h-5 rounded-[5px] border-2 border-slate-300 flex items-center justify-center transition
                                            {{ $arc['selected'] ? 'bg-emerald-500 border-emerald-500' : '' }}">
                                    <i class="fa-solid fa-check text-white text-xs {{ $arc['selected'] ? '' : 'hidden' }}"></i>
                                </div>
                                <div>
                                    <div class="archive-title text-sm font-semibold text-slate-900">{{ $arc['title'] }}</div>
                                    <div class="archive-sub text-[12.5px] text-slate-500">{{ $arc['sub'] }}</div>
                                </div>
                            </div>
                            <button class="archive-trash text-slate-400 hover:text-rose-500 text-[15px] p-2 rounded-md">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center mt-6 pt-4 border-t border-slate-200">
                        <div class="text-[13px] text-slate-500">
                            <span class="font-semibold text-slate-900" id="archive-count">84</span> records selected for archiving
                        </div>
                        <button class="bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg px-5 py-2.5 text-[13px] font-semibold inline-flex items-center gap-2 transition">
                            <i class="fa-solid fa-box-archive"></i> Archive Selected
                        </button>
                    </div>
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
    // 2. ARCHIVE ITEM SELECTION
    // ============================================================
    document.querySelectorAll('.archive-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.closest('.archive-trash')) return;
            item.classList.toggle('selected');
            const checkbox = item.querySelector('.archive-checkbox');
            const check = checkbox.querySelector('i');
            if (item.classList.contains('selected')) {
                checkbox.classList.add('bg-emerald-500','border-emerald-500');
                check.classList.remove('hidden');
                item.classList.add('border-emerald-500','bg-emerald-50');
            } else {
                checkbox.classList.remove('bg-emerald-500','border-emerald-500');
                check.classList.add('hidden');
                item.classList.remove('border-emerald-500','bg-emerald-50');
            }
            let total = 0;
            document.querySelectorAll('.archive-item.selected').forEach(sel => {
                const sub = sel.querySelector('.archive-sub').textContent;
                const m = sub.match(/\d+/);
                if (m) total += parseInt(m[0]);
            });
            document.getElementById('archive-count').textContent = total;
        });
    });

    // ============================================================
    // 3. EXPORT FORMAT SELECTION
    // ============================================================
    const formatCards = document.querySelectorAll('.export-format-card');
    formatCards.forEach(card => {
        card.addEventListener('click', () => {
            formatCards.forEach(c => {
                c.classList.remove('selected', 'border-2', 'border-emerald-500', 'bg-emerald-50');
                c.classList.add('border', 'border-slate-200', 'bg-white');

                const icon = c.querySelector('i');
                const title = c.querySelector('span:first-of-type');
                if (icon) icon.classList.remove('text-emerald-500');
                if (icon) icon.classList.add('text-slate-400');
                if (title) title.classList.remove('text-emerald-500');
                if (title) title.classList.add('text-slate-900');
            });

            card.classList.add('selected', 'border-2', 'border-emerald-500', 'bg-emerald-50');
            card.classList.remove('border', 'border-slate-200', 'bg-white');

            const icon = card.querySelector('i');
            const title = card.querySelector('span:first-of-type');
            if (icon) icon.classList.remove('text-slate-400');
            if (icon) icon.classList.add('text-emerald-500');
            if (title) title.classList.remove('text-slate-900');
            if (title) title.classList.add('text-emerald-500');
        });
    });
})();
</script>
@endpush