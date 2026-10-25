// resources/js/Pages/Auth/Login.jsx
import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Login() {
    const { props } = usePage();
    const flashError = props.flash?.error;
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleLogin = (e) => {
        e.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    const handleGoogleLogin = () => {
        const url = new URL('/auth/google', window.location.origin);
        if (data.remember) url.searchParams.set('remember', '1');
        window.location.href = url.toString();
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Login | JLM Cozy Cabin" />

            <div className="login-container">
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo"><img src="/images/jlm-logo.png" alt="Logo" /></div>
                        <span>JLM Cozy Cabin</span>
                    </div>
                    <div className="login-intro">
                        <h1>Your Comfortable<br />Living <span>Space Awaits</span></h1>
                        <p>Manage your rental experience with our comprehensive tenant portal</p>
                        <div className="login-benefits">
                            <div className="login-benefit"><span><i className="bi bi-check"></i></span><p>Submit maintenance requests instantly</p></div>
                            <div className="login-benefit"><span><i className="bi bi-check"></i></span><p>Track payments and billing history</p></div>
                            <div className="login-benefit"><span><i className="bi bi-check"></i></span><p>Browse available rooms virtually</p></div>
                            <div className="login-benefit"><span><i className="bi bi-check"></i></span><p>Stay updated with announcements</p></div>
                        </div>
                    </div>
                </div>

                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleLogin}>
                        <Link href="/" className="back-home-link"><i className="bi bi-arrow-left"></i> Back to Home</Link>
                        <h2>Welcome Back!</h2>
                        <p className="login-subtitle">Sign in to access your tenant portal</p>

                        {flashError && <div className="login-global-error"><i className="bi bi-exclamation-circle"></i>{flashError}</div>}
                        {errors.email && <div className="login-global-error"><i className="bi bi-exclamation-circle"></i>{errors.email}</div>}

                        <div className="login-form-group">
                            <label>Email Address</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-envelope"></i></span>
                                <input type="email" placeholder="name@example.com" value={data.email} onChange={(e) => setData('email', e.target.value)} autoComplete="email" autoFocus />
                            </div>
                        </div>

                        <div className="login-form-group">
                            <label>Password</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-lock"></i></span>
                                <input type={showPassword ? 'text' : 'password'} placeholder="Enter your password" value={data.password} onChange={(e) => setData('password', e.target.value)} autoComplete="current-password" />
                                <button type="button" className="password-toggle" onClick={() => setShowPassword(!showPassword)}>
                                    <i className={showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                </button>
                            </div>
                        </div>

                        <div className="login-options">
                            <label className="remember-me">
                                <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                                <span>Remember me</span>
                            </label>
                            <Link href="/forgot-password">Forgot password?</Link>
                        </div>

                        <button type="submit" className="login-submit-btn" disabled={processing} style={{ opacity: processing ? 0.7 : 1 }}>
                            {processing ? <><i className="bi bi-arrow-repeat" style={{ animation: 'spin 1s linear infinite' }}></i> Signing in...</> : 'Log In'}
                        </button>

                        <div className="login-divider"><span></span><p>OR</p><span></span></div>

                        <button type="button" onClick={handleGoogleLogin} className="google-login-btn" style={{ width: '100%', padding: '12px 16px', borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 10, fontWeight: 600, background: '#fff', border: '1.5px solid #e2e8f0', cursor: 'pointer' }}>
                            <strong style={{ fontSize: 18, color: '#4285f4' }}>G</strong> Continue with Google
                        </button>

                        <p className="signup-text">Don't have an account? <Link href="/signup">Sign up</Link></p>
                    </form>
                </div>
            </div>
        </div>
    );
}