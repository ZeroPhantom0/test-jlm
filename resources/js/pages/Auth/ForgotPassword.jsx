// resources/js/pages/Auth/ForgotPassword.jsx
import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function ForgotPassword() {
    const { props } = usePage();
    const flash = props.flash || {};

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Forgot Password | JLM Cozy Cabin" />

            <div className="login-container">
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo"><img src="/images/jlm-logo.png" alt="Logo" /></div>
                        <span>JLM Cozy Cabin</span>
                    </div>
                    <div className="login-intro">
                        <h1>Forgot Your<br /><span>Password?</span></h1>
                        <p>No worries — we'll send you a secure reset link. Just enter the email address you signed up with.</p>
                        <div className="login-benefits">
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Secure, one-time reset link</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Link expires in 60 minutes</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Your current password stays safe</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleSubmit}>
                        <Link href="/login" className="back-home-link">
                            <i className="bi bi-arrow-left"></i> Back to Login
                        </Link>

                        <h2>Reset Password</h2>
                        <p className="login-subtitle">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>

                        {/* Success Message */}
                        {flash.success && (
                            <div style={{ background: '#ecfdf5', color: '#065f46', padding: '10px 14px', borderRadius: 8, marginBottom: 16, fontSize: 13 }}>
                                <i className="bi bi-check-circle" style={{ marginRight: 8 }}></i>
                                {flash.success}
                            </div>
                        )}

                        {/* Flash Error (if any) */}
                        {flash.error && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {flash.error}
                            </div>
                        )}

                        {/* Validation Error (Email not found) */}
                        {errors.email && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {errors.email}
                            </div>
                        )}

                        <div className="login-form-group">
                            <label>Email Address</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-envelope"></i></span>
                                <input
                                    type="email"
                                    placeholder="name@example.com"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    autoComplete="email"
                                    autoFocus
                                    required
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            className="login-submit-btn"
                            disabled={processing}
                            style={{ opacity: processing ? 0.7 : 1 }}
                        >
                            {processing ? (
                                <>
                                    <i className="bi bi-arrow-repeat" style={{ animation: 'spin 1s linear infinite' }}></i>
                                    {' '}Verifying…
                                </>
                            ) : (
                                'Send Reset Link'
                            )}
                        </button>

                        <p className="signup-text">
                            Remember your password? <Link href="/login">Back to Log In</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}