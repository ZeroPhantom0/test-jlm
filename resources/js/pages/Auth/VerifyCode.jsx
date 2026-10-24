// resources/js/pages/Auth/VerifyCode.jsx
import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function VerifyCode({ email }) {
    const { props } = usePage();
    const flash = props.flash || {};

    const [digits, setDigits] = useState(['', '', '', '', '', '']);
    const [resending, setResending] = useState(false);
    const inputRefs = useRef([]);

    // 1. Add `code` to the useForm data object
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm({ 
        code: '' 
    });

    useEffect(() => { 
        inputRefs.current[0]?.focus(); 
    }, []);

    // 2. Update both the digits state and the Inertia form data
    const updateDigits = (newDigits) => {
        setDigits(newDigits);
        setData('code', newDigits.join(''));
    };

    const handleChange = (index, value) => {
        const digit = value.replace(/\D/g, '').slice(-1);
        const next = [...digits];
        next[index] = digit;
        updateDigits(next);
        
        if (digit && index < 5) inputRefs.current[index + 1]?.focus();
    };

    const handleKeyDown = (index, e) => {
        if (e.key === 'Backspace' && !digits[index] && index > 0) {
            inputRefs.current[index - 1]?.focus();
        }
    };

    const handlePaste = (e) => {
        e.preventDefault();
        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        if (!pasted) return;
        const next = ['', '', '', '', '', ''];
        pasted.split('').forEach((d, i) => (next[i] = d));
        updateDigits(next);
        inputRefs.current[Math.min(pasted.length, 5)]?.focus();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        clearErrors();
        
        // 3. Check the Inertia data (or the digits array) before submitting
        if (data.code.length !== 6) {
            return setError('code', 'Please enter all 6 digits.');
        }
        
        // 4. Submit just using `post(url, options)`
        post('/login/verify', { preserveScroll: true });
    };

    const handleResend = () => {
        setResending(true);
        post('/login/resend', { preserveScroll: true, onFinish: () => setResending(false) });
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Verify Login | JLM Cozy Cabin" />
            <div className="login-container">
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo"><img src="/images/jlm-logo.png" alt="Logo" /></div>
                        <span>JLM Cozy Cabin</span>
                    </div>
                    <div className="login-intro">
                        <h1>One More Step<br />to Your <span>Cozy Space</span></h1>
                        <p>We sent a verification code to confirm it's really you.</p>
                    </div>
                </div>
                <div className="login-right">
                    <div className="login-form-container">
                        <h2>Check Your Email</h2>
                        <p className="login-subtitle">Enter the 6-digit code we sent to <strong>{email}</strong></p>

                        {flash.success && <div style={{ background: '#ecfdf5', color: '#065f46', padding: '10px 14px', borderRadius: 8, marginBottom: 16, fontSize: 13 }}><i className="bi bi-check-circle" style={{ marginRight: 8 }}></i>{flash.success}</div>}
                        {flash.error && <div className="login-global-error"><i className="bi bi-exclamation-circle"></i>{flash.error}</div>}
                        {errors.code && <div className="login-global-error"><i className="bi bi-exclamation-circle"></i>{errors.code}</div>}

                        <form onSubmit={handleSubmit}>
                            <div style={{ display: 'flex', gap: 10, justifyContent: 'space-between', margin: '24px 0' }}>
                                {digits.map((digit, index) => (
                                    <input
                                        key={index}
                                        ref={(el) => { inputRefs.current[index] = el; }}
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={1}
                                        value={digit}
                                        onChange={(e) => handleChange(index, e.target.value)}
                                        onKeyDown={(e) => handleKeyDown(index, e)}
                                        onPaste={handlePaste}
                                        disabled={processing}
                                        style={{ width: 52, height: 60, textAlign: 'center', fontSize: 24, fontWeight: 600, border: '1.5px solid #cbd5e1', borderRadius: 10, outline: 'none' }}
                                    />
                                ))}
                            </div>
                            <button 
                                type="submit" 
                                className="login-submit-btn" 
                                disabled={processing || digits.join('').length !== 6} 
                                style={{ opacity: processing || digits.join('').length !== 6 ? 0.6 : 1 }}
                            >
                                {processing ? 'Verifying...' : 'Verify & Sign In'}
                            </button>
                        </form>

                        <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 20, fontSize: 13 }}>
                            <button type="button" onClick={handleResend} disabled={resending || processing} style={{ background: 'none', border: 'none', color: '#0d9488', fontWeight: 600, cursor: 'pointer', padding: 0 }}>
                                {resending ? 'Sending...' : 'Resend code'}
                            </button>
                            <Link href="/login" onClick={(e) => { e.preventDefault(); post('/login/cancel'); }} style={{ color: '#64748b', textDecoration: 'none' }}>
                                Use a different account
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}