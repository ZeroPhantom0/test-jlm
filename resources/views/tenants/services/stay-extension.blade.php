@extends('layouts.tenant', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
<div class="p-4 md:p-8 max-w-6xl">

    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-500 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Stay Extension Requests</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Request to extend your lease period</p>
        </div>
    </div>

    <div class="bg-blue-50 rounded-xl px-5 py-3.5 flex items-center gap-2.5 text-blue-600 text-[13.5px] mb-6">
        <i class="fa-regular fa-circle-info"></i>
        <span><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved, it becomes view-only.</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">1 request(s)</span>
            <button id="open-new-stay-extension" class="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[13px] text-slate-600 border-b border-slate-100">
                        <th class="py-3 px-4 font-semibold">Name & Room</th>
                        <th class="py-3 px-4 font-semibold">Current Lease End</th>
                        <th class="py-3 px-4 font-semibold">Requested End</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-4 px-4">
                            <span class="block font-semibold text-slate-900">Elle Dela Cruz</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Room 101</span>
                        </td>
                        <td class="py-4 px-4 text-[13.5px]">Apr 24, 2026</td>
                        <td class="py-4 px-4 text-[13.5px]">Jul 23, 2026</td>
                        <td class="py-4 px-4">
                            <span class="bg-amber-100 text-amber-700 text-[11.5px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                <i class="fa-regular fa-clock"></i> Pending
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex justify-end gap-3 text-slate-400 text-[15px]">
                                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600" id="view-stay-extension-details"></i>
                                <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-slate-600"></i>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="services-stay-extension-root"></div>
@endsection