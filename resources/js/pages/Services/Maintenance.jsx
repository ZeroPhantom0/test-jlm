import React, { useState } from 'react';
import Sidebar from '../../components/Sidebar';
import TopHeader from '../../components/TopHeader';
import MaintenanceModals from './MaintenanceModals'; 

export default function Maintenance() {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState(null);

    // Sample Maintenance Data
    const [requests] = useState([
        {
            id: 1,
            title: 'Leaky Faucet',
            description: 'Bathroom sink faucet is leaking continuously.',
            urgency: 'Medium',
            preferredDate: '2026-03-22',
            preferredTime: '10:00 AM - 12:00 PM',
            status: 'Pending',
            createdAt: 'Mar 21, 2026',
        },
        {
            id: 2,
            title: 'Broken Window Lock',
            description: 'Main room window lock cannot latch properly.',
            urgency: 'High',
            preferredDate: '2026-03-23',
            preferredTime: '02:00 PM - 04:00 PM',
            status: 'Pending',
            createdAt: 'Mar 21, 2026',
        },
    ]);

    return (
        <div className="flex min-h-screen bg-[#f8fafc]">
            {/* Sidebar with active key */}
            <Sidebar active="services.maintenance" />

            <div className="flex-1 flex flex-col min-w-0">
                <TopHeader title="Services" />

                <main className="p-[28px] max-w-[1200px] w-full flex flex-col gap-[20px]">
                    {/* Title Section */}
                    <div className="flex items-center gap-4">
                        <div className="w-[44px] h-[44px] bg-[#fef3c7] text-[#d97706] rounded-xl flex items-center justify-center text-lg">
                            <i className="fa-solid fa-wrench" />
                        </div>
                        <div>
                            <h2 className="text-[20px] font-bold text-slate-900">Maintenance Requests</h2>
                            <p className="text-[13px] text-slate-500 mt-[2px]">Report and track maintenance issues in your unit</p>
                        </div>
                    </div>

                    {/* Info Banner */}
                    <div className="bg-[#eff6ff] rounded-lg p-[12px_18px] flex items-center gap-2.5 text-[#2563eb] text-[13px]">
                        <i className="fa-solid fa-circle-info" />
                        <span>
                            For urgent or emergency issues, please contact the building admin directly at <strong>+63 912 345 6789</strong>.
                        </span>
                    </div>

                    {/* Table Card Container */}
                    <div className="bg-white rounded-xl border border-slate-200 p-[20px_24px] shadow-sm">
                        <div className="flex justify-between items-center mb-[20px]">
                            <span className="text-[13px] text-slate-500">
                                Showing {requests.length} maintenance request(s)
                            </span>
                            <button
                                type="button"
                                onClick={() => setIsCreateOpen(true)}
                                className="bg-[#02122c] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-md text-[13px] font-semibold flex items-center gap-2 transition-colors"
                            >
                                <i className="fa-solid fa-plus text-xs" />
                                <span>New Request</span>
                            </button>
                        </div>

                        {/* Table */}
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-slate-100 text-slate-600 text-[12px] font-semibold">
                                        <th className="py-3 px-2">Request</th>
                                        <th className="py-3 px-2">Urgency</th>
                                        <th className="py-3 px-2">Preferred Slot</th>
                                        <th className="py-3 px-2">Status</th>
                                        <th className="py-3 px-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {requests.map((req) => (
                                        <tr key={req.id} className="border-b border-slate-50 hover:bg-slate-50/50">
                                            <td className="py-4 px-2">
                                                <div className="font-semibold text-slate-800 text-[13px]">{req.title}</div>
                                                <div className="text-[12px] text-slate-400 mt-0.5 line-clamp-1">{req.description}</div>
                                            </td>
                                            <td className="py-4 px-2">
                                                <span className="bg-[#fef3c7] text-[#d97706] text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">
                                                    {req.urgency}
                                                </span>
                                            </td>
                                            <td className="py-4 px-2 text-[13px] text-slate-600">
                                                {req.preferredDate} ({req.preferredTime})
                                            </td>
                                            <td className="py-4 px-2">
                                                <span className="bg-[#fef3c7] text-[#d97706] text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1.5">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-[#d97706]" />
                                                    {req.status}
                                                </span>
                                            </td>
                                            <td className="py-4 px-2 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedRequest(req)}
                                                    className="text-slate-400 hover:text-slate-600 p-1"
                                                    title="View Details"
                                                >
                                                    <i className="fa-solid fa-eye text-sm" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
            </div>

            {/* Modals */}
            <MaintenanceModals
                isCreateOpen={isCreateOpen}
                onCloseCreate={() => setIsCreateOpen(false)}
                selectedRequest={selectedRequest}
                onCloseDetails={() => setSelectedRequest(null)}
            />
        </div>
    );
}