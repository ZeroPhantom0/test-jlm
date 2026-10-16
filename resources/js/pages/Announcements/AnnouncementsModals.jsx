// resources/js/pages/Announcements/AnnouncementsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const DEFAULT_RULES = {
    title:       'JLM Cozy Cabin - House Rules & Boarding Contract',
    author:      'Leah Administrator',
    posted_date: 'September 28, 2026',
    effective:   'January 2026',
    sections: [
        {
            heading: 'House Rules & Boarding Contract',
            body:    'By residing at JLM Cozy Cabin, tenants agree to comply with the following rules and policies:',
        },
        {
            heading: 'No Visitors',
            body:    'Visitors are not allowed inside tenant rooms or within the boarding house premises without prior approval from management.',
        },
        {
            heading: 'Quiet Hours',
            body:    'Quiet hours are observed from 10:00 PM to 7:00 AM. Excessive noise, disruptive behavior, loud music, and activities that may disturb other tenants are prohibited.',
        },
        {
            heading: 'Cleanliness & Sanitation',
            body:    'Tenants are responsible for maintaining the cleanliness of their rooms and shared areas. Garbage, food, and used dishes must be properly disposed of to prevent pests and maintain a healthy environment.',
        },
        {
            heading: 'Safety & Security',
            body:    'Cooking inside rooms is prohibited. Smoking, vaping, open flames, illegal substances, and other hazardous activities are strictly prohibited within the building.',
        },
        {
            heading: 'Room and Property Care',
            body:    'Take proper care of rooms, furniture, appliances, and other property.',
        },
        {
            heading: 'Respect for Property',
            body:    'Tenants must respect the belongings of others and may not use, borrow, or remove items without permission. Unauthorized attachments, decorations, or modifications to walls and property are prohibited.',
        },
        {
            heading: 'Room Access Policy',
            body:    "Entering another tenant's room without permission is strictly prohibited.",
        },
        {
            heading: 'Room Assignment',
            body:    'Tenants may only occupy their assigned room/bed.',
        },
        {
            heading: 'Utilities & Facilities',
            body:    'Tenants must use water, electricity, and boarding house facilities responsibly. Personal appliances may be subject to approval by management.',
        },
        {
            heading: 'Guests and Additional Occupants',
            body:    'Additional occupants must be properly declared and approved according to the applicable policy.',
        },
        {
            heading: 'Damages and Violations',
            body:    'Tenants may be responsible for damages caused by negligence or violations of the house rules.',
        },
        {
            heading: 'Laundry Policy',
            body:    'Clothes may only be hung in designated laundry areas. Hanging clothes in hallways, bathrooms, fire exits, or other unauthorized locations is prohibited.',
        },
        {
            heading: 'Payment Obligations',
            body:    'Rentals fees and other charges must be paid on or before the designated due date. Failure to comply with payment requirements may result in penalties or other actions in accordance with boarding house policies.',
        },
        {
            heading: 'Move-In and Move-Out',
            body:    'Tenants must comply with all move-in and move-out requirements. Personal belongings left after checkout remain the responsibility of the tenant, and JLM Cozy Cabin shall not be liable for any loss or damage.',
        },
        {
            heading: 'Alcohol and Prohibited Activities',
            body:    'Drinking alcoholic beverages and engaging in activities that may disturb, endanger, or inconvenience other tenants are prohibited within the premises.',
        },
        {
            heading: 'Financial Transactions',
            body:    'Borrowing, lending, or requesting loans from employees is prohibited to avoid misunderstandings and disputes.',
        },
    ],
    violations_intro: 'Failure to comply with these rules may result in the following:',
    violations: [
        { level: 'First Warning',  action: 'Verbal reminder' },
        { level: 'Second Warning', action: 'Written notice' },
        { level: 'Third Warning',  action: 'Applicable penalty' },
        { level: 'Final Warning',  action: 'Contract cancellation, termination of stay, or other appropriate disciplinary action' },
    ],
    closing: 'JLM Cozy Cabin reserves the right to enforce these policies to maintain a safe, secure, and comfortable living environment for all tenants.',
};

export default function AnnouncementsModals() {
    const [showRules, setShowRules] = useState(false);
    const [rules, setRules]         = useState(DEFAULT_RULES);

    // ============================================================
    // Read rules data from mount element (optional override from Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('tenant-announcements-modals');
        if (!el) return;

        if (el.dataset.rules) {
            try {
                const parsed = JSON.parse(el.dataset.rules);
                setRules({ ...DEFAULT_RULES, ...parsed });
            } catch (e) {
                console.error('Invalid rules JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation — open modal
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('#viewRulesBtn')) {
                setShowRules(true);
            }
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    const closeRules = () => setShowRules(false);

    if (!showRules) return null;

    return (
        <Modal onClose={closeRules} maxWidth="max-w-[650px]">
            {/* ============ HEADER ============ */}
            <div className="px-6 py-5 flex items-start justify-between border-b border-slate-100">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-lg shrink-0">
                        <i className="fa-solid fa-file-contract"></i>
                    </div>
                    <div>
                        <p className="text-[11px] font-bold text-amber-600 uppercase tracking-wider">
                            Official House Rules & Contract
                        </p>
                        <h2 className="text-lg font-bold text-slate-900 leading-snug">
                            {rules.title}
                        </h2>
                    </div>
                </div>
                <button
                    onClick={closeRules}
                    className="text-slate-400 hover:text-slate-700 text-lg p-1 transition"
                >
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>

            {/* ============ BODY ============ */}
            <div className="px-6 py-6 overflow-y-auto">
                {/* Meta */}
                <div className="flex items-center gap-2 text-[12px] text-slate-400 mb-5 pb-4 border-b border-slate-100">
                    <span>
                        Posted by <strong className="text-slate-600 font-semibold">{rules.author}</strong>
                    </span>
                    <span>•</span>
                    <span>{rules.posted_date}</span>
                </div>

                {/* Rules Content */}
                <div className="flex flex-col gap-5">
                    {rules.sections.map((s, i) => (
                        <div key={i}>
                            <h4 className="text-[14px] font-bold text-slate-900 mb-1.5">
                                {s.heading}
                            </h4>
                            <p className="text-[13px] text-slate-600 leading-relaxed">
                                {s.body}
                            </p>
                        </div>
                    ))}

                    {/* Violations Section */}
                    <div>
                        <h4 className="text-[14px] font-bold text-slate-900 mb-1.5">
                            ⚠ Violations and Disciplinary Actions
                        </h4>
                        <p className="text-[13px] text-slate-600 leading-relaxed">
                            {rules.violations_intro}
                        </p>
                        <ul className="list-none mt-1.5">
                            {rules.violations.map((v, i) => (
                                <li
                                    key={i}
                                    className="text-[13px] text-slate-600 leading-relaxed relative pl-4 mb-1"
                                >
                                    <span className="absolute left-0 text-slate-600 font-bold">•</span>
                                    <strong className="text-slate-900">{v.level}:</strong> {v.action}
                                </li>
                            ))}
                        </ul>
                        <p className="text-[13px] text-slate-600 leading-relaxed mt-3">
                            {rules.closing}
                        </p>
                    </div>
                </div>
            </div>

            {/* ============ FOOTER ============ */}
            <div className="px-6 py-4 border-t border-slate-100 flex justify-end bg-slate-50 rounded-b-2xl">
                <button
                    onClick={closeRules}
                    className="bg-[#02122c] hover:bg-[#0d2347] text-white px-6 py-2.5 rounded-lg text-[13px] font-bold transition"
                >
                    Close
                </button>
            </div>
        </Modal>
    );
}