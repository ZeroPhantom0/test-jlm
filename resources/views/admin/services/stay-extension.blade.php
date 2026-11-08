{{-- resources/views/admin/services/stay-extension.blade.php --}}
@extends('layouts.admin', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
    @php
        // Only pending requests are actionable on this page
        $activeRequests = $requests->filter(fn($req) => $req->status === 'Pending')->values();
    @endphp

    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg">
                    <i class="fa-regular fa-calendar-plus"></i>
                </div>
                <div>
                    <h2 class="text-[22px] font-extrabold text-slate-900">Stay Extension Requests</h2>
                    <p class="text-[13px] text-slate-500 mt-0.5">Manage pending tenant lease extension applications</p>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
                <span class="text-[13px] text-slate-500 font-medium">Total</span>
                <div class="text-[22px] font-bold text-slate-900 mt-2" id="metric-total">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
                <span class="text-[13px] text-slate-500 font-medium">Pending</span>
                <div class="mt-2">
                    <div class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold" id="metric-pending">
                        {{ $metrics['pending'] }}</div>
                </div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
                <span class="text-[13px] text-slate-500 font-medium">Approved</span>
                <div class="mt-2">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold" id="metric-approved">
                        {{ $metrics['approved'] }}</div>
                </div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
                <span class="text-[13px] text-slate-500 font-medium">Rejected</span>
                <div class="mt-2">
                    <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold" id="metric-rejected">
                        {{ $metrics['rejected'] }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                            <th class="py-3.5 px-5">Tenant</th>
                            <th class="py-3.5 px-5">Current Lease End</th>
                            <th class="py-3.5 px-5">Requested End</th>
                            <th class="py-3.5 px-5">Extension</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5"></th>
                        </tr>
                    </thead>
                    <tbody id="stay-extension-tbody">
                        @forelse($activeRequests as $req)
                            <tr class="border-b border-slate-50 last:border-0" data-request-id="{{ $req->id }}">
                                <td class="py-4 px-5">
                                    {{-- FIXED: Accessing relationship safely --}}
                                    <div class="text-sm font-bold text-slate-900">
                                        {{ $req->tenant?->user?->name ?? 'Unknown Tenant' }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $req->room_number }}</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                        <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i>
                                        {{ $req->current_lease_end }}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-purple-700 font-semibold">
                                        <i class="fa-regular fa-calendar text-purple-600 text-[13px]"></i>
                                        {{ $req->requested_lease_end }}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block bg-purple-100 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded-xl">
                                        {{ $req->extension_label }}
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $req->status_class }}">{{ $req->status }}</span>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center justify-end gap-3 text-slate-400 text-sm">
                                        <i class="fa-regular fa-circle-check text-emerald-500 cursor-pointer hover:text-emerald-600 row-approve-btn"
                                            data-request-id="{{ $req->id }}" title="Approve"></i>
                                        <i class="fa-regular fa-circle-xmark text-rose-500 cursor-pointer hover:text-rose-600 row-reject-btn"
                                            data-request-id="{{ $req->id }}" title="Reject"></i>
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-700 open-details-btn"
                                            data-request-id="{{ $req->id }}" title="View Details"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400">
                                    <i class="fa-solid fa-inbox text-4xl mb-3 block"></i>
                                    <p class="text-sm">No pending stay extension requests found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="admin-stay-extension-modals" data-requests='@json($requestsForReact)'></div>
@endsection

@push('scripts')
    <script>
        (function() {
            window.addEventListener('load', () => {
                async function refreshTable() {
                    try {
                        const res = await fetch("{{ route('admin.services.stay-extension.data') }}", {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            cache: 'no-store'
                        });
                        if (!res.ok) return;
                        const data = await res.json();

                        for (const [key, val] of Object.entries(data.metrics || {})) {
                            const el = document.getElementById(`metric-${key}`);
                            if (el) el.textContent = val;
                        }

                        const tbody = document.getElementById('stay-extension-tbody');
                        if (tbody && data.rows) {
                            const active = data.rows.filter(r => r.is_pending);

                            if (active.length === 0) {
                                tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400">
                                    <i class="fa-solid fa-inbox text-4xl mb-3 block"></i>
                                    <p class="text-sm">No pending stay extension requests found.</p>
                                </td>
                            </tr>
                        `;
                            } else {
                                tbody.innerHTML = active.map(r => `
                            <tr class="border-b border-slate-50 last:border-0" data-request-id="${r.id}">
                                <td class="py-4 px-5">
                                    {{-- FIXED: Fallback to r.tenant if r.tenant_name is empty --}}
                                    <div class="text-sm font-bold text-slate-900">${r.tenant_name || r.tenant || 'Unknown'}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">${r.room_number}</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                        <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i> ${r.current_lease_end}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-purple-700 font-semibold">
                                        <i class="fa-regular fa-calendar text-purple-600 text-[13px]"></i> ${r.requested_lease_end}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block bg-purple-100 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded-xl">${r.extension_label}</span>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${r.status_class}">${r.status}</span>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center justify-end gap-3 text-slate-400 text-sm">
                                        <i class="fa-regular fa-circle-check text-emerald-500 cursor-pointer hover:text-emerald-600 row-approve-btn" data-request-id="${r.id}" title="Approve"></i>
                                        <i class="fa-regular fa-circle-xmark text-rose-500 cursor-pointer hover:text-rose-600 row-reject-btn" data-request-id="${r.id}" title="Reject"></i>
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-700 open-details-btn" data-request-id="${r.id}" title="View Details"></i>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                            }
                        }

                        const modalEl = document.getElementById('admin-stay-extension-modals');
                        if (modalEl && data.react) {
                            modalEl.dataset.requests = JSON.stringify(data.react);
                            window.dispatchEvent(new CustomEvent('stay-extension:data-updated', {
                                detail: data.react
                            }));
                        }
                    } catch (e) {
                        console.error('Failed to refresh stay extensions:', e);
                    }
                }

                if (window.Echo) {
                    window.Echo.private('admin.services')
                        .listen('.stay-extension.updated', () => {
                            setTimeout(refreshTable, 800);
                        });
                }

                window.addEventListener('stay-extension:refresh', refreshTable);
            });
        })();
    </script>
@endpush