@extends('layouts.tenant', ['active' => 'settings', 'pageTitle' => 'Settings'])
@section('title', 'Settings')

@section('content')

{{-- React mount for accordion toggle behavior --}}
<div id="tenant-settings-accordions"></div>

<div class="p-4 md:p-8 max-w-6xl mx-auto">

    {{-- Page header --}}
    <div class="flex items-center gap-4 mb-7">
        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0">
            <i class="fa-solid fa-gear"></i>
        </div>
        <div class="min-w-0">
            <h2 class="text-xl font-extrabold text-slate-900">Account Settings</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Configure building management preferences</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start" id="settings-root">

        {{-- ==================== TABS ==================== --}}
        <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 shadow-sm">
            <button type="button"
                    class="settings-tab active flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold transition-all bg-[#02122c] text-white"
                    data-tab="profile">
                <div class="flex items-center gap-3">
                    <i class="fa-regular fa-user"></i>
                    <span>Profile</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="security">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-lock"></i>
                    <span>Security</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="preferences">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-palette"></i>
                    <span>Preferences</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="terms">
                <div class="flex items-center gap-3">
                    <i class="fa-regular fa-file-lines"></i>
                    <span>Terms &amp; Conditions</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="privacy">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Privacy Policy</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>

            <button type="button"
                    class="settings-tab flex items-center justify-between px-3.5 py-3 rounded-lg text-[13.5px] font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                    data-tab="contact">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-headset"></i>
                    <span>Contact &amp; Help</span>
                </div>
                <i class="fa-solid fa-chevron-right text-[11px] chevron hidden"></i>
            </button>
        </div>

        {{-- ==================== PANELS ==================== --}}
        <div class="min-w-0">

            {{-- ---------- PROFILE ---------- --}}
            <div class="settings-panel bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-profile">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Personal Information</h3>
                <p class="text-[13px] text-slate-500 mb-6">Update your profile details</p>

                <div class="flex items-center gap-4 mb-6">
                    <img src="https://i.pravatar.cc/100?img=47" class="w-16 h-16 rounded-full object-cover" alt="Elle">
                    <div>
                        <h4 class="text-[15px] font-bold text-slate-900">Elle Dela Cruz</h4>
                        <div class="text-[12.5px] text-slate-500 mb-1">Tenant</div>
                        <label for="profile-photo-input" class="text-emerald-500 text-[12.5px] font-semibold cursor-pointer hover:underline">
                            Change photo
                        </label>
                        <input type="file" id="profile-photo-input" accept="image/*" class="hidden">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Full Name</label>
                    <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="Elle Dela Cruz">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">Email Address</label>
                        <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="elledelacruz01@gmail.com">
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">Phone Number</label>
                        <input class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white" value="09121409895">
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Role</label>
                    <input readonly class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 text-[13.5px] text-slate-500 cursor-not-allowed" value="Tenant">
                </div>
                <div class="flex justify-end">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-regular fa-floppy-disk"></i> Save Profile
                    </button>
                </div>
            </div>

            {{-- ---------- SECURITY ---------- --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-security">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Change Password</h3>
                <p class="text-[13px] text-slate-500 mb-6">Update your account password</p>

                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Current Password</label>
                    <div class="relative">
                        <input type="password" value="123456789012"
                               class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 pr-10 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white password-input">
                        <span class="absolute inset-y-0 right-3.5 flex items-center cursor-pointer">
                            <i class="fa-regular fa-eye text-slate-400 password-toggle"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">New Password</label>
                    <div class="relative">
                        <input type="password" placeholder="Min. 8 characters"
                               class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 pr-10 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white password-input">
                        <span class="absolute inset-y-0 right-3.5 flex items-center cursor-pointer">
                            <i class="fa-regular fa-eye text-slate-400 password-toggle"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-[13px] font-semibold text-slate-700 mb-2">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" placeholder="Repeat new password"
                               class="w-full border border-slate-100 bg-slate-50 rounded-lg px-3.5 py-2.5 pr-10 text-[13.5px] outline-none focus:border-slate-300 focus:bg-white password-input">
                        <span class="absolute inset-y-0 right-3.5 flex items-center cursor-pointer">
                            <i class="fa-regular fa-eye text-slate-400 password-toggle"></i>
                        </span>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-solid fa-lock"></i> Update Password
                    </button>
                </div>
            </div>

            {{-- ---------- PREFERENCES ---------- --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-preferences">
                <h3 class="text-[17px] font-bold text-slate-900 mb-1">Preferences</h3>
                <p class="text-[13px] text-slate-500 mb-6">Customize your boarding house experience</p>

                @foreach([
                    ['id' => 'email', 'label' => 'Email Notifications', 'default' => true],
                    ['id' => 'sms', 'label' => 'SMS Alerts', 'default' => false],
                    ['id' => 'push', 'label' => 'Push Notifications', 'default' => false],
                ] as $pref)
                <div class="flex items-center justify-between py-4 border-b border-slate-50 last:border-0">
                    <div class="flex items-start gap-3">
                        <i class="fa-regular fa-bell text-base text-slate-900 mt-0.5"></i>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $pref['label'] }}</div>
                            <div class="text-xs text-slate-400 mt-0.5" id="{{ $pref['id'] }}-status">{{ $pref['default'] ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                    </div>
                    <label class="relative inline-block w-11 h-6 cursor-pointer">
                        <input type="checkbox" id="{{ $pref['id'] }}-toggle" @if($pref['default']) checked @endif class="peer opacity-0 w-0 h-0">
                        <span class="absolute inset-0 bg-slate-200 rounded-full transition peer-checked:bg-emerald-500"></span>
                        <span class="absolute left-[3px] bottom-[3px] bg-white w-[18px] h-[18px] rounded-full transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
                @endforeach

                <div class="flex justify-end mt-7">
                    <button class="bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 transition">
                        <i class="fa-regular fa-floppy-disk"></i> Save Appearance
                    </button>
                </div>
            </div>

            {{-- ---------- TERMS & CONDITIONS ---------- --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-terms">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-regular fa-file-lines text-slate-500 text-[18px]"></i>
                    <h3 class="text-[17px] font-bold text-slate-900">JLM Cozy Cabin Terms &amp; Conditions</h3>
                </div>
                <p class="text-[13px] text-slate-500 mb-6">Read all sections carefully. Click each section heading to expand and view the full content.</p>

                <div class="flex flex-col gap-4">

                    {{-- Accordion: General T&C --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="general">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">General Terms and Conditions</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">These Terms and Conditions ("Terms") establish the rules, responsibilities, and general conditions governing the use of the JLM Cozy Cabin Boarding House Management System and the accommodation services provided by JLM Cozy Cabin. By creating an account, submitting an application or reservation, or residing at JLM Cozy Cabin, tenants acknowledge that they have read, understood, and agreed to comply with these Terms and the applicable policies of the boarding house.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">1. Eligibility and Accommodation</h5>
                                <p class="mb-3">Tenants must be at least eighteen (18) years old to apply for accommodation and use the services of JLM Cozy Cabin. Applicants below eighteen (18) years old may only be accommodated with the involvement and consent of a parent or legal guardian, subject to the applicable requirements of the boarding house.</p>
                                <p class="mb-3">JLM Cozy Cabin operates as a boarding house and is intended for residential or extended stays rather than hotel or transient lodging services. Long-term occupants may be required to enter into a formal boarding agreement before or upon occupancy.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">2. Accuracy of Information</h5>
                                <p class="mb-3">Tenants are responsible for providing accurate, complete, and updated information when creating an account, completing their profile, submitting identification documents, making reservations, and providing payment information. Providing false, misleading, altered, or unauthorized information may result in the rejection or cancellation of a reservation or other appropriate action in accordance with applicable policies.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">3. Account Responsibility</h5>
                                <p class="mb-3">Each tenant is responsible for maintaining the confidentiality of their account credentials and for all activities performed through their account. Tenants must not share their account credentials with other individuals or allow unauthorized persons to access their account.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">4. Proper Use of the System</h5>
                                <p class="mb-3">The JLM Cozy Cabin Boarding House Management System must be used only for legitimate purposes related to accommodation, reservations, billing, communication, and other authorized boarding house services. Tenants must not use the system to submit fraudulent information, impersonate another person, interfere with system operations, or perform any activity that may compromise the security or proper operation of the system.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">5. Verification and Approval</h5>
                                <p class="mb-3">JLM Cozy Cabin reserves the right to review and verify information submitted through the system, including tenant information, identification documents, reservation details, and payment proofs. Submission of a reservation or payment proof does not automatically constitute approval or confirmation. Reservations and payments are subject to verification by authorized JLM Cozy Cabin personnel.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">6. Compliance with Policies</h5>
                                <p class="mb-3">Tenants are required to comply with the applicable JLM Cozy Cabin policies, including the Reservation Policy, Billing and Payment Policy, Privacy Policy, House Rules, and other official policies or notices communicated through the system.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">7. Violations and Restrictions</h5>
                                <p class="mb-3">Failure to comply with these Terms or applicable JLM Cozy Cabin policies may result in appropriate action, including the rejection or cancellation of reservations, restriction or suspension of system access, or other actions permitted under the applicable boarding agreement and policies.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">8. System Availability</h5>
                                <p class="mb-3">The JLM Cozy Cabin Boarding House Management System may occasionally become unavailable due to maintenance, system updates, technical issues, connectivity problems, or circumstances beyond the control of JLM Cozy Cabin. Reasonable efforts may be made to restore system availability and maintain the accuracy and security of system information.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">9. Communication and Notifications</h5>
                                <p class="mb-3">Tenants are responsible for providing accurate and updated contact information. Important account, reservation, payment, service, and other official updates may be communicated through the notification channels available in the system, including in-system notifications, email, or SMS when applicable.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">10. Changes to the Terms</h5>
                                <p class="mb-3">JLM Cozy Cabin reserves the right to update or modify these Terms when necessary. Significant changes may be communicated through official announcements or the contact information registered in the system. Continued use of the system or continued occupancy after the updated Terms take effect constitutes acknowledgment of the updated Terms, subject to applicable laws and agreements.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">11. Governing Law</h5>
                                <p class="mb-3">These Terms shall be governed by and interpreted in accordance with the applicable laws of the Republic of the Philippines.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">12. Acceptance of the Terms</h5>
                                <p class="mb-3">By creating an account, submitting a reservation, or continuing to use the JLM Cozy Cabin Boarding House Management System, the tenant acknowledges that they have read, understood, and agreed to these General Terms and Conditions and the applicable JLM Cozy Cabin policies.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion: Reservation Policy --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="reservation">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Reservation Policy</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">All reservations at JLM Cozy Cabin are subject to room availability and the submission of complete and accurate reservation information.</p>
                                <p class="mb-3">Tenants must provide the required reservation details and select an available payment option before submitting their reservation. The available payment options may include a fifty percent (50%) down payment or full payment, depending on the applicable reservation requirements.</p>
                                <p class="mb-3">After submission, the reservation will be placed under Pending Verification status and will not be considered confirmed until reviewed by the Admin. The Admin will review the reservation details and submitted payment information before approving or rejecting the reservation.</p>
                                <p class="mb-3">An approved reservation will be confirmed by the system and the tenant will be notified of the reservation status. If the reservation does not meet the applicable requirements, the Admin may reject it and provide an appropriate reason.</p>
                                <p class="mb-3">Reservations may expire if the required requirements are not completed within the specified period or if the tenant fails to arrive on the scheduled move-in date, subject to the applicable reservation conditions.</p>
                                <p class="mb-3">Reservation cancellations, changes to reservation details, and room changes are subject to room availability, applicable policies, and Admin approval.</p>
                                <p class="mb-3">Upon move-in, tenants may be required to present a valid identification document and reservation confirmation for verification.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion: Payment and Billing Policy --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="payment">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Payment and Billing Policy</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">Tenants are responsible for reviewing their billing information, payment amounts, due dates, and remaining balances shown in the system.</p>
                                <p class="mb-3">Required payments must be settled within the specified due date through the payment methods provided by JLM Cozy Cabin. Tenants must upload the required proof of payment when submitting a payment through the system.</p>
                                <p class="mb-3">Payments submitted through the system are subject to verification by the Admin. A payment will only be recorded as Verified once the submitted payment information and proof have been reviewed and confirmed.</p>
                                <p class="mb-3">Verified payments will be recorded in the tenant's payment history and an official payment receipt may be made available through the system. Pending or rejected payments will not be treated as verified payments.</p>
                                <p class="mb-3">Any discrepancy or concern regarding billing information or payment records should be reported to the Admin for review.</p>
                                <p class="mb-3">Failure to settle required payments within the applicable due date may result in the payment being marked as overdue. Additional charges, restrictions, or other actions may also be applied in accordance with JLM Cozy Cabin policies and the applicable boarding agreement.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ---------- PRIVACY POLICY ---------- --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-privacy">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-solid fa-shield-halved text-slate-500 text-[18px]"></i>
                    <h3 class="text-[17px] font-bold text-slate-900">JLM Cozy Cabin Privacy Policy</h3>
                </div>
                <p class="text-[13px] text-slate-500 mb-6">Learn how your personal information is collected, used, and protected.</p>

                <div class="flex flex-col gap-4">

                    {{-- Purpose of Data Collection --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="purpose">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Purpose of Data Collection</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">The personal information collected by JLM Cozy Cabin is used for the following purposes:</p>
                                <ul class="list-none pl-0 mb-3">
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Verifying tenant identity</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Processing reservations and room assignments</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Managing billing and payments</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Handling emergency and safety concerns</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Sending important announcements and updates</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Processing maintenance requests and tenant concerns</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Maintaining accurate tenant records</li>
                                </ul>
                                <p class="mb-3">We collect only the information that is necessary and proportionate to the purposes described above, in accordance with the principles of transparency, legitimate purpose, and proportionality under Republic Act No. 10173, or the Data Privacy Act of 2012.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Use and Storage of Data --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="use">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Use and Storage of Data</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">Your personal information is securely stored in the JLM Cozy Cabin management system and is accessible only to authorized personnel.</p>
                                <p class="mb-3">Personal information is kept only for as long as necessary for tenancy, reservations, billing, communication, and other legitimate purposes of the system.</p>
                                <p class="mb-3">JLM Cozy Cabin does not sell or use your personal information for unrelated commercial purposes. Information may only be shared when necessary for legitimate purposes or when required by law.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Access and Protection --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="access">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Access and Protection of Information</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">JLM Cozy Cabin implements appropriate technical, organizational, and physical security measures to protect your personal information against unauthorized access, disclosure, alteration, or destruction. These measures include:</p>
                                <ul class="list-none pl-0 mb-3">
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Restricted physical access to records storage</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Role-based system access controls</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Encrypted storage of digital records</li>
                                    <li class="relative pl-4 mb-1.5 before:content-['•'] before:absolute before:left-0 before:text-slate-600 before:font-bold">Regular review of access permissions</li>
                                </ul>
                                <p class="mb-3">Only authorized management personnel — specifically the property owner, administrator, and designated staff — are permitted to access tenant personal information. Access is granted on a need-to-know basis only.</p>
                                <p class="mb-3">In the event of a personal data breach that is likely to result in risk to your rights and freedoms, we will notify the National Privacy Commission and affected data subjects within seventy-two (72) hours of becoming aware of the breach, as required by the Data Privacy Act.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Your Data Rights --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="rights">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Your Data Rights</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">Under the Data Privacy Act of 2012, you have the following rights regarding your personal information:</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO INFORMATION</h5>
                                <p class="mb-3">You have the right to know how your personal information is collected, used, and processed.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO ACCESS</h5>
                                <p class="mb-3">You may request access to the personal information we hold about you.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO CORRECTION</h5>
                                <p class="mb-3">You may request the correction of inaccurate or incomplete personal information.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO ERASURE OR BLOCKING</h5>
                                <p class="mb-3">You may request the deletion or blocking of your personal information when allowed by law.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO OBJECT</h5>
                                <p class="mb-3">You may object to certain processing of your personal information when allowed by law.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO DATA PORTABILITY</h5>
                                <p class="mb-3">Where applicable, you may request your personal information in a commonly used electronic format.</p>

                                <h5 class="text-[13.5px] font-bold text-slate-900 mt-4 mb-1.5">RIGHT TO LODGE A COMPLAINT</h5>
                                <p class="mb-3">You may raise a privacy concern or file a complaint if you believe your data privacy rights have been violated.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Privacy Concerns --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="concerns">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Privacy Concerns and Contact</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">Tenants may contact JLM Cozy Cabin regarding questions, concerns, or requests related to their personal information. Tenants may also request access to or correction of their personal information, subject to applicable laws and verification requirements. Privacy-related concerns may be submitted through the contact information or communication channels provided by JLM Cozy Cabin.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Updates to Privacy Policy --}}
                    <div class="settings-accordion bg-white border border-slate-100 rounded-xl overflow-hidden" data-accordion-key="updates">
                        <div class="settings-accordion-header px-6 py-[18px] flex items-center justify-between cursor-pointer select-none hover:bg-slate-50 transition">
                            <h4 class="text-[15px] font-semibold text-slate-900">Updates to the Privacy Policy</h4>
                            <i class="fa-solid fa-chevron-down settings-accordion-chevron text-slate-400 text-xs transition-transform duration-300"></i>
                        </div>
                        <div class="settings-accordion-body max-h-0 overflow-hidden transition-all duration-300 border-t border-transparent px-6">
                            <div class="pt-[18px] text-[13.5px] text-slate-600 leading-[1.7]">
                                <p class="mb-3">JLM Cozy Cabin may update this Privacy Policy / Data Privacy Notice when necessary to reflect changes in the system, services, data processing practices, or applicable requirements. Significant changes will be communicated through the system or other appropriate communication channels.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ---------- CONTACT & HELP ---------- --}}
            <div class="settings-panel hidden bg-white rounded-xl border border-slate-100 p-7 shadow-sm" id="panel-contact">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fa-solid fa-headset text-slate-500 text-[18px]"></i>
                    <h3 class="text-[17px] font-bold text-slate-900">Contact &amp; Help</h3>
                </div>
                <p class="text-[13px] text-slate-500 mb-6">Official contact information and support resources for JLM Cozy Cabin</p>

                {{-- Contact cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    {{-- Management Office --}}
                    <div class="bg-white border border-slate-100 rounded-xl p-5">
                        <h4 class="text-[15px] font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-building text-emerald-500 text-[16px]"></i> Management Office
                        </h4>
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Owner / Manager Phone</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">09171234567</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Official Email</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">jlmcozycabin@gmail.com</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-regular fa-comment-dots"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Owner / Manager</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">Leah Agujar</span>
                            </div>
                        </div>
                    </div>

                    {{-- Location & Hours --}}
                    <div class="bg-white border border-slate-100 rounded-xl p-5">
                        <h4 class="text-[15px] font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-map-pin text-emerald-500 text-[16px]"></i> Location &amp; Hours
                        </h4>
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Boarding House Address</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">1517 M.H. Del Pilar St. Ermita Manila</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-regular fa-clock"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Operating Hours</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">24/7</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-md bg-emerald-50 text-emerald-500 flex items-center justify-center text-[12px] shrink-0 mt-0.5">
                                <i class="fa-brands fa-facebook-f"></i>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[11px] text-slate-400 font-semibold uppercase">Facebook Page</span>
                                <span class="text-[13.5px] font-semibold text-slate-900">fb.com/jlmcozycabin</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Message banner --}}
                <div class="bg-[#02122c] rounded-xl px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4 text-white mb-6">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 bg-emerald-500/20 rounded-[10px] flex items-center justify-center text-emerald-400 text-[18px] shrink-0">
                            <i class="fa-regular fa-comment-dots"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold mb-0.5">Need to Send a Message?</h4>
                            <p class="text-xs text-slate-400">Use the Messages module to send a direct message to the admin.</p>
                        </div>
                    </div>
                    <a href="{{ url('/tenants/messages') }}"
                       class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center justify-center gap-2 transition shrink-0">
                        <i class="fa-regular fa-comment-dots"></i> Open Messages
                    </a>
                </div>

                {{-- Quick help --}}
                <h3 class="text-[15px] font-bold text-slate-900 mb-4">Quick Help Topics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white border border-slate-100 rounded-lg p-4 flex flex-col gap-2 hover:border-slate-200 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-[13.5px] font-bold text-slate-900">Report a Maintenance Issue</span>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-[10px]">Maintenance</span>
                        </div>
                        <div class="text-[12px] text-slate-500 leading-snug">Submit a request for repairs or service.</div>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-lg p-4 flex flex-col gap-2 hover:border-slate-200 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-[13.5px] font-bold text-slate-900">Request a Room Change</span>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-[10px]">Services</span>
                        </div>
                        <div class="text-[12px] text-slate-500 leading-snug">Apply to transfer to a different unit.</div>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-lg p-4 flex flex-col gap-2 hover:border-slate-200 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-[13.5px] font-bold text-slate-900">Pay Your Rent</span>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-[10px]">Billing</span>
                        </div>
                        <div class="text-[12px] text-slate-500 leading-snug">View and settle outstanding invoices.</div>
                    </div>
                    <div class="bg-white border border-slate-100 rounded-lg p-4 flex flex-col gap-2 hover:border-slate-200 transition">
                        <div class="flex items-center justify-between">
                            <span class="text-[13.5px] font-bold text-slate-900">View Announcements</span>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-[10px]">Info</span>
                        </div>
                        <div class="text-[12px] text-slate-500 leading-snug">Stay updated on building notices.</div>
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
    // TAB SWITCHING
    // ============================================================
    const tabs   = document.querySelectorAll('.settings-tab');
    const panels = document.querySelectorAll('.settings-panel');

    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(t => {
            t.classList.remove('bg-[#02122c]', 'text-white');
            t.classList.add('text-slate-600', 'hover:bg-slate-50');
            const ch = t.querySelector('.chevron');
            if (ch) ch.classList.add('hidden');
        });

        tab.classList.add('bg-[#02122c]', 'text-white');
        tab.classList.remove('text-slate-600', 'hover:bg-slate-50');
        const activeCh = tab.querySelector('.chevron');
        if (activeCh) activeCh.classList.remove('hidden');

        panels.forEach(p => p.classList.add('hidden'));
        document.getElementById('panel-' + tab.dataset.tab).classList.remove('hidden');
    }));

    // ============================================================
    // PREFERENCE TOGGLES — live status text
    // ============================================================
    ['email','sms','push'].forEach(id => {
        const t = document.getElementById(id + '-toggle');
        const s = document.getElementById(id + '-status');
        if (t) t.addEventListener('change', () => s.textContent = t.checked ? 'Enabled' : 'Disabled');
    });

    // ============================================================
    // PASSWORD VISIBILITY TOGGLES
    // ============================================================
    document.querySelectorAll('.password-toggle').forEach(icon => {
        icon.addEventListener('click', () => {
            const wrapper = icon.closest('.relative');
            const input = wrapper?.querySelector('.password-input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        });
    });

    // ============================================================
    // CHANGE PHOTO — trigger file input
    // ============================================================
    const photoInput = document.getElementById('profile-photo-input');
    if (photoInput) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (file) {
                const img = document.querySelector('#panel-profile img');
                if (img) img.src = URL.createObjectURL(file);
            }
        });
    }

    // ============================================================
    // ACCORDION INITIAL STATE — ALL COLLAPSED
    // ------------------------------------------------------------
    // The React component (`SettingsAccordions.jsx`) handles the
    // click toggle. This just enforces the collapsed baseline on
    // load so nothing appears pre-expanded.
    // ============================================================
    document.querySelectorAll('.settings-accordion').forEach(item => {
        item.classList.remove('expanded');
        const body = item.querySelector('.settings-accordion-body');
        const chev = item.querySelector('.settings-accordion-chevron');
        if (body) {
            body.style.maxHeight = '0px';
            body.style.padding   = '0 24px';
            body.style.borderTop = '1px solid transparent';
        }
        if (chev) chev.style.transform = 'rotate(0deg)';
    });
})();
</script>
@endpush