@extends('layouts.admin', ['active' => 'services.maintenance', 'pageTitle' => 'Services'])
@section('title', 'Maintenance Requests')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-wrench"></i>
        </div>
        <div>
            <h2 class="text-[22px] font-extrabold text-slate-900">Maintenance Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Track and resolve maintenance issues submitted by tenants</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div class="text-[22px] font-bold text-slate-900 mt-2">5</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">3</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">In Progress</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[13px] font-bold">1</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Completed</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">1</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col md:flex-row gap-4">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search by title, room, tenant..." class="w-full text-[13.5px] outline-none bg-transparent">
        </div>
        <select class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
            <option>All Statuses</option>
            <option>Pending</option>
            <option>In Progress</option>
            <option>Completed</option>
        </select>
        <select class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
            <option>All Priorities</option>
            <option>High</option>
            <option>Medium</option>
            <option>Low</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 tracking-wide uppercase border-b border-slate-200">
                    <th class="py-3.5 px-5 w-[28%]">Request</th>
                    <th class="py-3.5 px-5 w-[22%]">Room / Tenant</th>
                    <th class="py-3.5 px-5 w-[18%]">Assigned</th>
                    <th class="py-3.5 px-5 w-[14%]">Status</th>
                    <th class="py-3.5 px-5 w-[12%]">Date</th>
                    <th class="py-3.5 px-5 w-[6%]"></th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    ['title' => 'Leaky Faucet', 'priority' => 'MEDIUM', 'priorityClass' => 'bg-yellow-100 text-amber-700', 'category' => 'Plumbing', 'room' => 'Room 101', 'tenant' => 'Nicole Custorio', 'assigned' => 'Unassigned', 'status' => 'Pending', 'statusClass' => 'bg-yellow-100 text-amber-700', 'date' => 'Mar 20, 2026'],
                    ['title' => 'AC Not Cooling', 'priority' => 'HIGH', 'priorityClass' => 'bg-rose-100 text-rose-600', 'category' => 'Hvac', 'room' => 'Room 102', 'tenant' => 'Elle Dela Cruz', 'assigned' => 'Alden Staff', 'status' => 'In Progress', 'statusClass' => 'bg-blue-100 text-blue-600', 'date' => 'Mar 20, 2026'],
                    ['title' => 'Light Fixture Broken', 'priority' => 'LOW', 'priorityClass' => 'bg-blue-100 text-blue-600', 'category' => 'Electrical', 'room' => 'Room 104', 'tenant' => 'Mau Santos', 'assigned' => 'Alden Staff', 'status' => 'Completed', 'statusClass' => 'bg-emerald-100 text-emerald-600', 'date' => 'Mar 20, 2026'],
                    ['title' => 'Dishwasher Error', 'priority' => 'MEDIUM', 'priorityClass' => 'bg-yellow-100 text-amber-700', 'category' => 'Appliance', 'room' => 'Room 201', 'tenant' => 'Emily Garcia', 'assigned' => 'Unassigned', 'status' => 'Pending', 'statusClass' => 'bg-yellow-100 text-amber-700', 'date' => 'Mar 20, 2026'],
                    ['title' => 'Broken Window Lock', 'priority' => 'HIGH', 'priorityClass' => 'bg-rose-100 text-rose-600', 'category' => 'General', 'room' => 'Room 101', 'tenant' => 'Nicole Custorio', 'assigned' => 'Unassigned', 'status' => 'Pending', 'statusClass' => 'bg-yellow-100 text-amber-700', 'date' => 'Mar 19, 2026'],
                ] as $req)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="py-4 px-5">
                        <div class="font-bold text-slate-900 text-sm">{{ $req['title'] }}</div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded uppercase tracking-wide {{ $req['priorityClass'] }}">{{ $req['priority'] }}</span>
                            <span class="text-[12px] text-slate-400">{{ $req['category'] }}</span>
                        </div>
                    </td>
                    <td class="py-4 px-5">
                        <div class="font-semibold text-slate-700 text-[13px]">{{ $req['room'] }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $req['tenant'] }}</div>
                    </td>
                    <td class="py-4 px-5">
                        @if($req['assigned'] === 'Unassigned')
                            <span class="text-slate-400 italic text-[13px]">Unassigned</span>
                        @else
                            <span class="text-slate-600 font-medium text-[13px]">{{ $req['assigned'] }}</span>
                        @endif
                    </td>
                    <td class="py-4 px-5">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $req['statusClass'] }}">{{ $req['status'] }}</span>
                    </td>
                    <td class="py-4 px-5 text-[13px] text-slate-500">{{ $req['date'] }}</td>
                    <td class="py-4 px-5">
                        <button class="text-emerald-600 hover:text-emerald-700 text-[13px] font-semibold inline-flex items-center gap-1.5 admin-view-request-btn">
                            <i class="fa-regular fa-eye"></i> View
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="admin-services-modals"></div>
@endsection