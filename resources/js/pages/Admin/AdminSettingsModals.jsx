import React, { useState, useEffect } from "react";
import Modal from "../../components/Modal";

export default function AdminSettingsModals() {
    const [isOpen, setIsOpen] = useState(false);
    const [step, setStep] = useState(1); // 1 = Form, 2 = OTP
    const [actionType, setActionType] = useState(""); // email, phone, password
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    
    // Form State
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [currentPassword, setCurrentPassword] = useState("");
    const [newPassword, setNewPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [otp, setOtp] = useState("");

    // Listen for the custom event from the Blade file
    useEffect(() => {
        const handleOpen = (e) => {
            setActionType(e.detail.action);
            setIsOpen(true);
            setStep(1);
            setError("");
            setEmail("");
            setPhone("");
            setCurrentPassword("");
            setNewPassword("");
            setConfirmPassword("");
            setOtp("");
        };
        window.addEventListener("admin-settings:open-modal", handleOpen);
        return () => window.removeEventListener("admin-settings:open-modal", handleOpen);
    }, []);

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

    const closeModal = () => {
        if (loading) return;
        setIsOpen(false);
    };

    const handleRequestCode = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError("");

        const payload = { action_type: actionType };
        if (actionType === 'email') payload.email = email;
        if (actionType === 'phone') payload.phone = phone;
        if (actionType === 'password') {
            payload.current_password = currentPassword;
            payload.password = newPassword;
            payload.password_confirmation = confirmPassword;
        }

        try {
            const res = await fetch('/admin/settings/request-verification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf()
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (!res.ok) throw new Error(data.message || 'Validation failed');
            
            setStep(2); // Move to OTP step
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleConfirmCode = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError("");

        try {
            const res = await fetch('/admin/settings/confirm-verification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf()
                },
                body: JSON.stringify({ code: otp })
            });
            const data = await res.json();
            
            if (!res.ok) throw new Error(data.message || 'Invalid code');
            
            window.location.reload(); // Reload to reflect changes
        } catch (err) {
            setError(err.message);
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <Modal onClose={closeModal} maxWidth="max-w-[420px]">
            <div className="bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col">
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 className="text-lg font-bold text-slate-900">
                        {actionType === 'email' && 'Update Email Address'}
                        {actionType === 'phone' && 'Update Phone Number'}
                        {actionType === 'password' && 'Change Password'}
                    </h3>
                    <button onClick={closeModal} disabled={loading} className="text-slate-400 hover:text-slate-700">
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-6">
                    {error && (
                        <div className="bg-rose-50 text-rose-600 text-[13px] p-3 rounded-lg mb-4 flex items-start gap-2">
                            <i className="fa-solid fa-circle-exclamation mt-0.5"></i>
                            <span>{error}</span>
                        </div>
                    )}

                    {step === 1 ? (
                        <form onSubmit={handleRequestCode} className="space-y-4">
                            
                            {actionType === 'email' && (
                                <div>
                                    <label className="block text-[13px] font-semibold text-slate-700 mb-2">New Email Address</label>
                                    <input type="email" required value={email} onChange={e => setEmail(e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                                </div>
                            )}

                            {actionType === 'phone' && (
                                <div>
                                    <label className="block text-[13px] font-semibold text-slate-700 mb-2">New Phone Number</label>
                                    <input type="text" required value={phone} onChange={e => setPhone(e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                                </div>
                            )}

                            {actionType === 'password' && (
                                <>
                                    <div>
                                        <label className="block text-[13px] font-semibold text-slate-700 mb-2">Current Password</label>
                                        <input type="password" required value={currentPassword} onChange={e => setCurrentPassword(e.target.value)}
                                            className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                                    </div>
                                    <div>
                                        <label className="block text-[13px] font-semibold text-slate-700 mb-2">New Password</label>
                                        <input type="password" required value={newPassword} onChange={e => setNewPassword(e.target.value)}
                                            className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                                    </div>
                                    <div>
                                        <label className="block text-[13px] font-semibold text-slate-700 mb-2">Confirm New Password</label>
                                        <input type="password" required value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)}
                                            className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                                    </div>
                                </>
                            )}

                            <div className="pt-2">
                                <button type="submit" disabled={loading} 
                                    className="w-full bg-[#02122c] hover:bg-[#091e42] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold transition flex justify-center items-center gap-2">
                                    {loading ? <i className="fa-solid fa-spinner fa-spin"></i> : 'Send Verification Code'}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <form onSubmit={handleConfirmCode} className="space-y-4">
                            <div className="text-center mb-6">
                                <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl mx-auto mb-3">
                                    <i className="fa-regular fa-envelope"></i>
                                </div>
                                <p className="text-[13px] text-slate-600 leading-relaxed">
                                    We sent a 6-digit code to your <strong>current email</strong>. Enter it below to confirm the change.
                                </p>
                            </div>
                            
                            <div>
                                <input type="text" required maxLength="6" value={otp} onChange={e => setOtp(e.target.value)}
                                    className="w-full border border-slate-200 rounded-lg px-3.5 py-3 text-center text-2xl tracking-[0.5em] font-mono outline-none focus:border-slate-400" 
                                    placeholder="000000" />
                            </div>

                            <div className="pt-2">
                                <button type="submit" disabled={loading || otp.length < 6} 
                                    className="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold transition flex justify-center items-center gap-2">
                                    {loading ? <i className="fa-solid fa-spinner fa-spin"></i> : 'Confirm Change'}
                                </button>
                            </div>
                            
                            <div className="text-center mt-3">
                                <button type="button" onClick={() => setStep(1)} disabled={loading} className="text-[12px] text-slate-500 hover:text-slate-800">
                                    Wait, let me change the input.
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </Modal>
    );
}