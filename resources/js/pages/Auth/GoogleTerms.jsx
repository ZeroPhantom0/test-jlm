// resources/js/pages/Auth/GoogleTerms.jsx
import React, { useEffect, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import TermsModal from './Modals/TermsModal';
import PrivacyModal from './Modals/PrivacyModal';

export default function GoogleTerms({ name, email }) {
    const [showGate, setShowGate] = useState(false);
    const [showTerms, setShowTerms] = useState(false);
    const [showPrivacy, setShowPrivacy] = useState(false);
    const [showCancelWarning, setShowCancelWarning] = useState(false);

    const [viewedTerms, setViewedTerms] = useState(false);
    const [viewedPrivacy, setViewedPrivacy] = useState(false);

    const { post, processing, setData } = useForm({ accepted: false });

    useEffect(() => { setShowGate(true); }, []);

    const openTerms = () => {
        setViewedTerms(true);
        setShowTerms(true);
    };

    const openPrivacy = () => {
        setViewedPrivacy(true);
        setShowPrivacy(true);
    };

    const canAccept = viewedTerms && viewedPrivacy;

    const handleConfirm = () => {
        setData('accepted', true);
        post('/auth/google/terms', { onSuccess: () => setShowGate(false) });
    };

    const handleAttemptClose = () => {
        setShowCancelWarning(true);
    };

    const handleConfirmCancel = () => {
        setShowCancelWarning(false);
        setShowGate(false);
        post('/auth/google/terms/cancel');
    };

    return (
        <div
            className="google-terms-page"
            style={{
                minHeight: '100vh',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: '#f8fafc',
                padding: 24,
            }}
        >
            <Head title="Accept Terms | JLM Cozy Cabin" />

            {/* Backdrop content behind the gate */}
            <div
                style={{
                    maxWidth: 480,
                    textAlign: 'center',
                    background: '#fff',
                    border: '1px solid #e2e8f0',
                    borderRadius: 16,
                    padding: 32,
                }}
            >
                <div
                    style={{
                        width: 64,
                        height: 64,
                        margin: '0 auto 16px',
                        borderRadius: '50%',
                        background: '#ecfdf5',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                    }}
                >
                    <i className="bi bi-google" style={{ fontSize: 28, color: '#4285f4' }}></i>
                </div>
                <h1 style={{ fontSize: 20, fontWeight: 700, color: '#0f172a', marginBottom: 8 }}>
                    One more step, {name?.split(' ')[0] || 'friend'}!
                </h1>
                <p style={{ fontSize: 14, color: '#64748b', marginBottom: 4 }}>
                    Signed in as <strong>{email}</strong>
                </p>
                <p style={{ fontSize: 13, color: '#94a3b8' }}>
                    Please review and accept our Terms and Privacy Policy to continue.
                </p>
            </div>

            {/* ───────── Gate modal ───────── */}
            {showGate && (
                <div
                    className="modal-overlay"
                    style={{ zIndex: 1000 }}
                    onClick={handleAttemptClose}
                >
                    <div
                        className="modal-content"
                        onClick={(e) => e.stopPropagation()}
                        style={{ maxWidth: 520 }}
                    >
                        <div className="modal-header">
                            <div className="flex items-center gap-3">
                                <i className="bi bi-google text-emerald-500 text-xl"></i>
                                <h3 className="modal-title">One more step</h3>
                            </div>
                            <button className="modal-close" onClick={handleAttemptClose} aria-label="Close">
                                <i className="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div className="modal-body">
                            <p className="text-sm text-slate-500 mb-4">
                                Before we create your account, please review and accept both of our
                                policies. Click each one to read it.
                            </p>

                            <div className="flex flex-col gap-3 mb-4">
                                <button
                                    type="button"
                                    onClick={openTerms}
                                    className="flex items-center justify-between w-full px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition text-left"
                                >
                                    <span className="flex items-center gap-3">
                                        <span className="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                            <i className="bi bi-file-earmark-text"></i>
                                        </span>
                                        <span className="flex flex-col">
                                            <span className="text-sm font-semibold text-slate-900">
                                                Terms and Conditions
                                            </span>
                                            <span className="text-[12px] text-slate-500">
                                                {viewedTerms ? 'Reviewed ✓' : 'Tap to read'}
                                            </span>
                                        </span>
                                    </span>
                                    <i className="bi bi-chevron-right text-slate-400"></i>
                                </button>

                                <button
                                    type="button"
                                    onClick={openPrivacy}
                                    className="flex items-center justify-between w-full px-4 py-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition text-left"
                                >
                                    <span className="flex items-center gap-3">
                                        <span className="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                            <i className="bi bi-shield-check"></i>
                                        </span>
                                        <span className="flex flex-col">
                                            <span className="text-sm font-semibold text-slate-900">
                                                Privacy Policy
                                            </span>
                                            <span className="text-[12px] text-slate-500">
                                                {viewedPrivacy ? 'Reviewed ✓' : 'Tap to read'}
                                            </span>
                                        </span>
                                    </span>
                                    <i className="bi bi-chevron-right text-slate-400"></i>
                                </button>
                            </div>

                            {!canAccept && (
                                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                    <p className="text-[12px] text-amber-800 leading-snug">
                                        <i className="bi bi-info-circle mr-1"></i>
                                        Please review <strong>both</strong> the Terms and the Privacy
                                        Policy before creating your account.
                                    </p>
                                </div>
                            )}
                        </div>

                        <div
                            className="modal-footer"
                            style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}
                        >
                            <button className="btn-secondary" onClick={handleAttemptClose}>
                                Cancel
                            </button>
                            <button
                                className="btn-primary bg-emerald-500 hover:bg-emerald-600 text-white"
                                onClick={handleConfirm}
                                disabled={processing || !canAccept}
                                style={{
                                    opacity: processing || !canAccept ? 0.5 : 1,
                                    cursor: processing || !canAccept ? 'not-allowed' : 'pointer',
                                }}
                            >
                                {processing ? 'Saving…' : 'I Agree'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Existing TermsModal — unchanged */}
            <TermsModal
                isOpen={showTerms}
                onClose={() => setShowTerms(false)}
                onConfirm={() => setShowTerms(false)}
            />

            {/* Existing PrivacyModal — unchanged */}
            <PrivacyModal
                isOpen={showPrivacy}
                onClose={() => setShowPrivacy(false)}
            />

            {/* Cancel warning — inline styled so it beats every other overlay */}
            {showCancelWarning && (
                <div
                    style={{
                        position: 'fixed',
                        inset: 0,
                        background: 'rgba(15, 23, 42, 0.55)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: 24,
                        zIndex: 9999,
                    }}
                    onClick={() => setShowCancelWarning(false)}
                >
                    <div
                        style={{
                            width: '100%',
                            maxWidth: 440,
                            background: '#fff',
                            borderRadius: 16,
                            overflow: 'hidden',
                            boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
                        }}
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div
                            style={{
                                padding: '16px 20px',
                                borderBottom: '1px solid #e2e8f0',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }}
                        >
                            <h3 style={{ fontSize: 16, fontWeight: 700, color: '#0f172a' }}>
                                Cancel account creation?
                            </h3>
                            <button
                                type="button"
                                onClick={() => setShowCancelWarning(false)}
                                style={{
                                    border: 0,
                                    background: 'transparent',
                                    fontSize: 18,
                                    color: '#94a3b8',
                                    cursor: 'pointer',
                                    lineHeight: 1,
                                }}
                                aria-label="Close"
                            >
                                <i className="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div style={{ padding: 24 }}>
                            <p style={{ fontSize: 13, color: '#475569', marginBottom: 12 }}>
                                If you cancel now, your account will <strong>not</strong> be created.
                                None of your information will be saved, and you will need to sign in
                                with Google again to continue.
                            </p>

                            <div
                                style={{
                                    background: '#fffbeb',
                                    border: '1px solid #fde68a',
                                    borderRadius: 12,
                                    padding: 12,
                                }}
                            >
                                <p style={{ fontSize: 12, color: '#92400e', lineHeight: 1.5 }}>
                                    <i className="bi bi-info-circle" style={{ marginRight: 6 }}></i>
                                    You must accept the <strong>Terms and Conditions</strong> and{' '}
                                    <strong>Privacy Policy</strong> to use the tenant portal. Without
                                    an account you cannot make reservations, view billing, or message
                                    building management.
                                </p>
                            </div>
                        </div>

                        <div
                            style={{
                                padding: '16px 20px',
                                borderTop: '1px solid #e2e8f0',
                                display: 'flex',
                                justifyContent: 'flex-end',
                                gap: 12,
                            }}
                        >
                            <button
                                type="button"
                                onClick={() => setShowCancelWarning(false)}
                                disabled={processing}
                                style={{
                                    padding: '10px 16px',
                                    borderRadius: 10,
                                    border: '1px solid #e2e8f0',
                                    background: '#fff',
                                    color: '#0f172a',
                                    fontSize: 13.5,
                                    fontWeight: 600,
                                    cursor: processing ? 'not-allowed' : 'pointer',
                                }}
                            >
                                Keep going
                            </button>
                            <button
                                type="button"
                                onClick={handleConfirmCancel}
                                disabled={processing}
                                style={{
                                    padding: '10px 16px',
                                    borderRadius: 10,
                                    border: 0,
                                    background: processing ? '#fecdd3' : '#f43f5e',
                                    color: '#fff',
                                    fontSize: 13.5,
                                    fontWeight: 600,
                                    cursor: processing ? 'wait' : 'pointer',
                                }}
                            >
                                {processing ? 'Cancelling…' : 'Yes, cancel'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}