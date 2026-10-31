// resources/js/pages/Auth/Modals/TermsModal.jsx
import React, { useState } from 'react';

export default function TermsModal({ isOpen, onClose, onConfirm }) {
    const [expanded, setExpanded] = useState(null);

    if (!isOpen) return null;

    const toggle = (key) => setExpanded(expanded === key ? null : key);

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <div className="flex items-center gap-3">
                        <i className="bi bi-file-earmark-text text-emerald-500 text-xl"></i>
                        <h3 className="modal-title">Terms and Conditions</h3>
                    </div>
                    <button className="modal-close" onClick={onClose}>
                        <i className="bi bi-x-lg"></i>
                    </button>
                </div>
                <div className="modal-body">
                    <p className="text-sm text-slate-500 mb-4">Please review and agree to our Terms and Conditions before creating your account.</p>
                    
                    <div className="flex flex-col gap-3">
                        {/* 1. General Terms and Conditions */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('general-terms')}
                            >
                                General Terms and Conditions
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'general-terms' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'general-terms' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200 max-h-60 overflow-y-auto">
                                    <p className="mb-2">These Terms and Conditions ("Terms") establish the rules, responsibilities, and general conditions governing the use of the JLM Cozy Cabin Boarding House Management System and the accommodation services provided by JLM Cozy Cabin. By creating an account, submitting an application or reservation, or residing at JLM Cozy Cabin, tenants acknowledge that they have read, understood, and agreed to comply with these Terms and the applicable policies of the boarding house.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">1. Eligibility and Accommodation</h5>
                                    <p className="mb-2">Tenants must be at least eighteen (18) years old to apply for accommodation and use the services of JLM Cozy Cabin. Applicants below eighteen (18) years old may only be accommodated with the involvement and consent of a parent or legal guardian, subject to the applicable requirements of the boarding house.</p>
                                    <p className="mb-2">JLM Cozy Cabin operates as a boarding house and is intended for residential or extended stays rather than hotel or transient lodging services. Long-term occupants may be required to enter into a formal boarding agreement before or upon occupancy.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">2. Accuracy of Information</h5>
                                    <p className="mb-2">Tenants are responsible for providing accurate, complete, and updated information when creating an account, completing their profile, submitting identification documents, making reservations, and providing payment information. Providing false, misleading, altered, or unauthorized information may result in the rejection or cancellation of a reservation or other appropriate action in accordance with applicable policies.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">3. Account Responsibility</h5>
                                    <p className="mb-2">Each tenant is responsible for maintaining the confidentiality of their account credentials and for all activities performed through their account. Tenants must not share their account credentials with other individuals or allow unauthorized persons to access their account.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">4. Proper Use of the System</h5>
                                    <p className="mb-2">The JLM Cozy Cabin Boarding House Management System must be used only for legitimate purposes related to accommodation, reservations, billing, communication, and other authorized boarding house services. Tenants must not use the system to submit fraudulent information, impersonate another person, interfere with system operations, or perform any activity that may compromise the security or proper operation of the system.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">5. Verification and Approval</h5>
                                    <p className="mb-2">JLM Cozy Cabin reserves the right to review and verify information submitted through the system, including tenant information, identification documents, reservation details, and payment proofs. Submission of a reservation or payment proof does not automatically constitute approval or confirmation. Reservations and payments are subject to verification by authorized JLM Cozy Cabin personnel.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">6. Compliance with Policies</h5>
                                    <p className="mb-2">Tenants are required to comply with the applicable JLM Cozy Cabin policies, including the Reservation Policy, Billing and Payment Policy, Privacy Policy, House Rules, and other official policies or notices communicated through the system.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">7. Violations and Restrictions</h5>
                                    <p className="mb-2">Failure to comply with these Terms or applicable JLM Cozy Cabin policies may result in appropriate action, including the rejection or cancellation of reservations, restriction or suspension of system access, or other actions permitted under the applicable boarding agreement and policies.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">8. System Availability</h5>
                                    <p className="mb-2">The JLM Cozy Cabin Boarding House Management System may occasionally become unavailable due to maintenance, system updates, technical issues, connectivity problems, or circumstances beyond the control of JLM Cozy Cabin. Reasonable efforts may be made to restore system availability and maintain the accuracy and security of system information.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">9. Communication and Notifications</h5>
                                    <p className="mb-2">Tenants are responsible for providing accurate and updated contact information. Important account, reservation, payment, service, and other official updates may be communicated through the notification channels available in the system, including in-system notifications, email, or SMS when applicable.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">10. Changes to the Terms</h5>
                                    <p className="mb-2">JLM Cozy Cabin reserves the right to update or modify these Terms when necessary. Significant changes may be communicated through official announcements or the contact information registered in the system. Continued use of the system or continued occupancy after the updated Terms take effect constitutes acknowledgment of the updated Terms, subject to applicable laws and agreements.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">11. Governing Law</h5>
                                    <p className="mb-2">These Terms shall be governed by and interpreted in accordance with the applicable laws of the Republic of the Philippines.</p>

                                    <h5 className="font-bold text-slate-900 mt-3 mb-1">12. Acceptance of the Terms</h5>
                                    <p>By creating an account, submitting a reservation, or continuing to use the JLM Cozy Cabin Boarding House Management System, the tenant acknowledges that they have read, understood, and agreed to these General Terms and Conditions and the applicable JLM Cozy Cabin policies.</p>
                                </div>
                            )}
                        </div>

                        {/* 2. Reservation Policy */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('reservation-policy')}
                            >
                                Reservation Policy
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'reservation-policy' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'reservation-policy' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200 max-h-60 overflow-y-auto">
                                    <p className="mb-2">All reservations at JLM Cozy Cabin are subject to room availability and the submission of complete and accurate reservation information.</p>
                                    <p className="mb-2">Tenants must provide the required reservation details and select an available payment option before submitting their reservation. The available payment options may include a fifty percent (50%) down payment or full payment, depending on the applicable reservation requirements.</p>
                                    <p className="mb-2">After submission, the reservation will be placed under Pending Verification status and will not be considered confirmed until reviewed by the Admin. The Admin will review the reservation details and submitted payment information before approving or rejecting the reservation.</p>
                                    <p className="mb-2">An approved reservation will be confirmed by the system and the tenant will be notified of the reservation status. If the reservation does not meet the applicable requirements, the Admin may reject it and provide an appropriate reason.</p>
                                    <p className="mb-2">Reservations may expire if the required requirements are not completed within the specified period or if the tenant fails to arrive on the scheduled move-in date, subject to the applicable reservation conditions.</p>
                                    <p className="mb-2">Reservation cancellations, changes to reservation details, and room changes are subject to room availability, applicable policies, and Admin approval.</p>
                                    <p>Upon move-in, tenants may be required to present a valid identification document and reservation confirmation for verification.</p>
                                </div>
                            )}
                        </div>

                        {/* 3. Payment and Billing Policy */}
                        <div className="border border-slate-200 rounded-lg overflow-hidden">
                            <button 
                                type="button" 
                                className="w-full flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition text-left font-semibold text-sm"
                                onClick={() => toggle('billing-policy')}
                            >
                                Payment and Billing Policy
                                <i className={`bi bi-chevron-down transition-transform ${expanded === 'billing-policy' ? 'rotate-180' : ''}`}></i>
                            </button>
                            {expanded === 'billing-policy' && (
                                <div className="px-4 py-3 text-sm text-slate-600 leading-relaxed border-t border-slate-200 max-h-60 overflow-y-auto">
                                    <p className="mb-2">Tenants are responsible for reviewing their billing information, payment amounts, due dates, and remaining balances shown in the system.</p>
                                    <p className="mb-2">Required payments must be settled within the specified due date through the payment methods provided by JLM Cozy Cabin. Tenants must upload the required proof of payment when submitting a payment through the system.</p>
                                    <p className="mb-2">Payments submitted through the system are subject to verification by the Admin. A payment will only be recorded as Verified once the submitted payment information and proof have been reviewed and confirmed.</p>
                                    <p className="mb-2">Verified payments will be recorded in the tenant's payment history and an official payment receipt may be made available through the system. Pending or rejected payments will not be treated as verified payments.</p>
                                    <p className="mb-2">Any discrepancy or concern regarding billing information or payment records should be reported to the Admin for review.</p>
                                    <p>Failure to settle required payments within the applicable due date may result in the payment being marked as overdue. Additional charges, restrictions, or other actions may also be applied in accordance with JLM Cozy Cabin policies and the applicable boarding agreement.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                <div className="modal-footer" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <button className="btn-secondary" onClick={onClose}>Cancel</button>
                    <button className="btn-primary bg-emerald-500 hover:bg-emerald-600 text-white" onClick={onConfirm}>
                        I Agree & Sign Up
                    </button>
                </div>
            </div>
        </div>
    );
}