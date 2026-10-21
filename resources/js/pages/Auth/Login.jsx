// resources/js/Pages/Auth/Login.jsx
import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleLogin = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Login | JLM Cozy Cabin" />

            <div className="login-container">
                {/* LEFT */}
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo">
                            <img src="/images/jlm-logo.png" alt="JLM Cozy Cabin Logo" />
                        </div>
                        <span>JLM Cozy Cabin</span>
                    </div>

                    <div className="login-intro">
                        <h1>
                            Your Comfortable
                            <br />
                            Living <span>Space Awaits</span>
                        </h1>
                        <p>Manage your rental experience with our comprehensive tenant portal</p>

                        <div className="login-benefits">
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Submit maintenance requests instantly</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Track payments and billing history</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Browse available rooms virtually</p>
                            </div>
                            <div className="login-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Stay updated with announcements</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* RIGHT */}
                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleLogin}>
                        <Link href="/" className="back-home-link">
                            <i className="bi bi-arrow-left"></i>
                            Back to Home
                        </Link>

                        <h2>Welcome Back!</h2>
                        <p className="login-subtitle">Sign in to access your tenant portal</p>

                        {/* Global error (bad credentials) */}
                        {errors.email && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {errors.email}
                            </div>
                        )}

                        {/* EMAIL */}
                        <div className="login-form-group">
                            <label htmlFor="loginEmail">Email Address</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-envelope"></i></span>
                                <input
                                    type="email"
                                    id="loginEmail"
                                    name="email"
                                    placeholder="name@example.com"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    autoComplete="email"
                                    autoFocus
                                />
                            </div>
                        </div>

                        {/* PASSWORD */}
                        <div className="login-form-group">
                            <label htmlFor="loginPassword">Password</label>
                            <div className="login-input-wrapper">
                                <span className="login-input-icon"><i className="bi bi-lock"></i></span>
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    id="loginPassword"
                                    name="password"
                                    placeholder="Enter your password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="current-password"
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
                        </div>

                        {/* OPTIONS */}
                        <div className="login-options">
                            <label className="remember-me">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <span>Remember me</span>
                            </label>
                            <a href="#">Forgot password?</a>
                        </div>

                        {/* LOGIN */}
                        <button
                            type="submit"
                            className="login-submit-btn"
                            disabled={processing}
                            style={{ opacity: processing ? 0.7 : 1, cursor: processing ? 'wait' : 'pointer' }}
                        >
                            {processing ? (
                                <>
                                    <i className="bi bi-arrow-repeat" style={{ animation: 'spin 1s linear infinite' }}></i>
                                    Signing in...
                                </>
                            ) : (
                                'Log In'
                            )}
                        </button>

                        <div className="login-divider">
                            <span></span>
                            <p>OR</p>
                            <span></span>
                        </div>

                        <Link href="/signup" className="google-login-btn" style={{ textDecoration: 'none' }}>
                            <strong>G</strong>
                            Continue with Google
                        </Link>

                        <p className="signup-text">
                            Don't have an account? <Link href="/signup">Sign up</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}