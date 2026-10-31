// resources/js/pages/Auth/Modals/PrivacyModal.jsx
import React, { useState } from 'react';

export default function PrivacyModal({ isOpen, onClose }) {
    const [expanded, setExpanded] = useState(null);

    if (!isOpen) return null;

    const toggle = (key) => setExpanded(expanded === key ? null : key);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <div className="flex items-center gap-3">
                        <i className="bi bi-shield-check text-emerald-500 text-xl"></i>
                        <h3 className="modal-title">JLM Cozy Cabin Privacy Policy</h3>
                    </div>
                    <button className="modal-close" onClick={onClose}>
                        <i className="bi bi-x-lg"></i>
                    </button>
                </div>
                <div className="modal-body">
                    <p className="text-sm text-slate-500 mb-4">Learn how your personal information is collected, used, and protected.</p>
                    
                    <div className="flex flex-col gap-3">
                        {/* 1. Personal Information Collected */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('personal-info')}
                            >
                                Personal Information Collected
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'personal-info' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'personal-info' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p className="mb-2">In accordance with Republic Act No. 10173, also known as the Data Privacy Act of 2012, JLM Cozy Cabin collects and processes the following personal information from tenants and applicants:</p>
                                    <ul className="list-disc pl-5 mb-2 space-y-1">
                                        <li>Full legal name</li>
                                        <li>Contact number and email address</li>
                                        <li>Current address</li>
                                        <li>Government-issued identification documents (e.g., National ID, Passport, Driver's License, UMID)</li>
                                        <li>Emergency contact name and contact number</li>
                                        <li>Profile photograph</li>
                                        <li>Reservation and accommodation information</li>
                                        <li>Payment-related information, including payment method and uploaded proof of payment</li>
                                    </ul>
                                    <p>Sensitive personal information such as government ID numbers are handled with the highest level of care and are accessed only by authorized management personnel.</p>
                                </div>
                            )}
                        </div>

                        {/* 2. Purpose of Data Collection */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('purpose')}
                            >
                                Purpose of Data Collection
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'purpose' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'purpose' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p className="mb-2">The personal information collected by JLM Cozy Cabin is used for the following purposes:</p>
                                    <ul className="list-disc pl-5 mb-2 space-y-1">
                                        <li>Verifying tenant identity</li>
                                        <li>Processing reservations and room assignments</li>
                                        <li>Managing billing and payments</li>
                                        <li>Handling emergency and safety concerns</li>
                                        <li>Sending important announcements and updates</li>
                                        <li>Processing maintenance requests and tenant concerns</li>
                                        <li>Maintaining accurate tenant records</li>
                                    </ul>
                                    <p>We collect only the information that is necessary and proportionate to the purposes described above, in accordance with the principles of transparency, legitimate purpose, and proportionality under Republic Act No. 10173, or the Data Privacy Act of 2012.</p>
                                </div>
                            )}
                        </div>

                        {/* 3. Data Storage, Account Inactivity, and Retention */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('retention')}
                            >
                                Data Storage, Account Inactivity, and Retention
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'retention' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'retention' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p className="mb-3">JLM Cozy Cabin retains personal information only for as long as necessary to support tenant management, reservations, billing, communication, and other legitimate purposes, subject to applicable legal and regulatory requirements.</p>
                                    
                                    <h5 className="font-bold text-slate-900 mb-1">1. Account Inactivity</h5>
                                    <p className="mb-3">An account may be considered inactive when the user has not logged in or performed any account-related activity for six (6) consecutive months. Accounts associated with ongoing tenancy, active reservations, or unsettled billing obligations shall not be automatically deactivated solely due to inactivity.</p>

                                    <h5 className="font-bold text-slate-900 mb-1">2. Inactivity Notification – After 6 Months</h5>
                                    <p className="mb-3">After six (6) consecutive months of inactivity, JLM Cozy Cabin may send an inactivity reminder through the user's registered email address or other available communication channels. The notification will inform the user about the account's inactivity and possible deactivation if no further activity occurs.</p>

                                    <h5 className="font-bold text-slate-900 mb-1">3. Account Deactivation – After 12 Months</h5>
                                    <p className="mb-3">Accounts that remain inactive for twelve (12) consecutive months may be deactivated, provided that no active tenancy, reservation, or outstanding obligation requires continued account access. Prior notice shall be provided where practicable.</p>
                                    <p className="mb-3">Deactivated users may request account reactivation by contacting the administrator, subject to identity verification and applicable security procedures.</p>

                                    <h5 className="font-bold text-slate-900 mb-1">4. Data Retention Review – After 24 Months</h5>
                                    <p className="mb-3">After twenty-four (24) consecutive months of inactivity, JLM Cozy Cabin shall review the personal information associated with the account to determine whether continued retention remains necessary.</p>
                                    <p className="mb-3">Personal information that is no longer required for legitimate purposes shall be securely deleted or anonymized, subject to applicable legal obligations and justified retention requirements.</p>
                                    <p className="mb-3">Reservation histories, billing records, payment transactions, and other necessary records may be retained separately when required for legitimate operational, legal, or recordkeeping purposes.</p>

                                    <h5 className="font-bold text-slate-900 mb-1">5. Account Deletion Requests</h5>
                                    <p className="mb-3">Users may request the deletion of their accounts and associated personal information by contacting the JLM Cozy Cabin administrator. Requests shall be reviewed in accordance with applicable data privacy laws.</p>
                                    <p>Information that must be retained for legitimate or legal purposes may be excluded from immediate deletion and shall remain protected until its retention period ends.</p>
                                </div>
                            )}
                        </div>

                        {/* 4. Access and Protection of Information */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('access')}
                            >
                                Access and Protection of Information
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'access' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'access' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p className="mb-2">JLM Cozy Cabin implements appropriate technical, organizational, and physical security measures to protect your personal information against unauthorized access, disclosure, alteration, or destruction. These measures include:</p>
                                    <ul className="list-disc pl-5 mb-2 space-y-1">
                                        <li>Restricted physical access to records storage</li>
                                        <li>Role-based system access controls</li>
                                        <li>Encrypted storage of digital records</li>
                                        <li>Regular review of access permissions</li>
                                    </ul>
                                    <p className="mb-2">Only authorized management personnel — specifically the property owner, administrator, and designated staff — are permitted to access tenant personal information. Access is granted on a need-to-know basis only.</p>
                                    <p>In the event of a personal data breach that is likely to result in risk to your rights and freedoms, we will notify the National Privacy Commission and affected data subjects within seventy-two (72) hours of becoming aware of the breach, as required by the Data Privacy Act.</p>
                                </div>
                            )}
                        </div>

                        {/* 5. Your Data Rights */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('rights')}
                            >
                                Your Data Rights
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'rights' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'rights' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p className="mb-2">Under the Data Privacy Act of 2012, you have the following rights regarding your personal information:</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO INFORMATION</h5>
                                    <p className="mb-2">You have the right to know how your personal information is collected, used, and processed.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO ACCESS</h5>
                                    <p className="mb-2">You may request access to the personal information we hold about you.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO CORRECTION</h5>
                                    <p className="mb-2">You may request the correction of inaccurate or incomplete personal information.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO ERASURE OR BLOCKING</h5>
                                    <p className="mb-2">You may request the deletion or blocking of your personal information when allowed by law.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO OBJECT</h5>
                                    <p className="mb-2">You may object to certain processing of your personal information when allowed by law.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO DATA PORTABILITY</h5>
                                    <p className="mb-2">Where applicable, you may request your personal information in a commonly used electronic format.</p>
                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">RIGHT TO LODGE A COMPLAINT</h5>
                                    <p>You may raise a privacy concern or file a complaint if you believe your data privacy rights have been violated.</p>
                                </div>
                            )}
                        </div>

                        {/* 6. Privacy Concerns and Contact */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('concerns')}
                            >
                                Privacy Concerns and Contact
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'concerns' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'concerns' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p>Tenants may contact JLM Cozy Cabin regarding questions, concerns, or requests related to their personal information. Tenants may also request access to or correction of their personal information, subject to applicable laws and verification requirements. Privacy-related concerns may be submitted through the contact information or communication channels provided by JLM Cozy Cabin.</p>
                                </div>
                            )}
                        </div>

                        {/* 7. Updates to the Privacy Policy */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('updates')}
                            >
                                Updates to the Privacy Policy
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'updates' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'updates' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200">
                                    <p>JLM Cozy Cabin may update this Privacy Policy / Data Privacy Notice when necessary to reflect changes in the system, services, data processing practices, or applicable requirements. Significant changes will be communicated through the system or other appropriate communication channels.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                <div className="modal-footer">
                    <button className="btn-secondary" onClick={onClose}>Close</button>
                </div>
            </div>
        </div>
    );
}