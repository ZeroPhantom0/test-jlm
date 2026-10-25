// resources/js/pages/Auth/ResetPassword.jsx
import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function ResetPassword({ token, email: initialEmail }) {
    const { props } = usePage();
    const flash = props.flash || {};

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm]   = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        token: token || '',
        email: initialEmail || '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/password/reset');
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Reset Password | JLM Cozy Cabin" />

            <div className="login-container">
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo"><img src="/images/jlm-logo.png" alt="Logo" /></div>
                        <span>JLM Cozy Cabin</span>
                    </div>
                    <div className="login-intro">
                        <h1>Create a<br /><span>New Password</span></h1>
                        <p>Choose a strong password you haven't used before. Once saved, you'll be able to sign in immediately.</p>
                        <div className="login-benefits">
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Minimum 8 characters</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Mix letters, numbers, and symbols</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Keep it unique to JLM Cozy Cabin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleSubmit}>
                        <Link href="/login" className="back-home-link">
                            <i className="bi bi-arrow-left"></i> Back to Login
                        </Link>

                        <h2>Set New Password</h2>
                        <p className="login-subtitle">
                            Enter a new password for <strong>{data.email}</strong>
                        </p>

                        {flash.error && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {flash.error}
                            </div>
                        )}
                        {errors.email && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {errors.email}
                            </div>
                        )}
                        {errors.token && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {errors.token}
                            </div>
                        )}

                        {/* New password */}
                        <div className="login-form-group">
                            <label>New Password</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-lock"></i></span>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    placeholder="Enter new password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="new-password"
                                    autoFocus
                                    required
                                />
                                <button
                                    type="button"
                                    className="password-toggle"
                                    onClick={() => setShowPassword(!showPassword)}
                                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                                >
                                    <i className={showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                </button>
                            </div>
                            {errors.password && (
                                <div className="login-global-error" style={{ marginTop: 8 }}>
                                    <i className="bi bi-exclamation-circle"></i>
                                    {errors.password}
                                </div>
                            )}
                        </div>

                        {/* Confirm password */}
                        <div className="login-form-group">
                            <label>Confirm New Password</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-lock"></i></span>
                                <input
                                    type={showConfirm ? 'text' : 'password'}
                                    placeholder="Confirm new password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    autoComplete="new-password"
                                    required
                                />
                                <button
                                    type="button"
                                    className="password-toggle"
                                    onClick={() => setShowConfirm(!showConfirm)}
                                    aria-label={showConfirm ? 'Hide password' : 'Show password'}
                                >
                                    <i className={showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                </button>
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
                                    {' '}Saving…
                                </>
                            ) : (
                                'Reset Password'
                            )}
                        </button>

                        <p className="signup-text">
                            Remembered it? <Link href="/login">Back to Log In</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}