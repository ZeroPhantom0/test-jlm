// resources/js/pages/Auth/Login.jsx
import React from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { LoginAlert, LoginField, useLoginValidation } from '../../components/auth/LoginForm';

export default function Login() {
    const { props } = usePage();
    const flashError = props.flash?.error;

    const form = useForm({
        email: '',
        password: '',
    });
    const { data, post, processing, reset } = form;
    const validation = useLoginValidation(form);

    const handleLogin = (e) => {
        e.preventDefault();
        if (!validation.validateAll()) return;

        post('/login', {
            preserveScroll: true,
            onFinish: () => reset('password'),
        });
    };

    const handleGoogleLogin = () => {
        window.location.href = new URL('/auth/google', window.location.origin).toString();
    };

    const handleBackHome = (e) => {
        e.preventDefault();
        router.visit('/', { replace: true });
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Login | JLM Cozy Cabin" />

            <div className="login-container">
                {/* ===== LEFT SIDE (custom bg + info) ===== */}
                <div
                    className="login-left"
                    style={{
                        backgroundImage:
                            "linear-gradient(rgba(0,29,76,0.72), rgba(0,29,76,0.72)), url('/images/bg-login.jpg')",
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                        backgroundRepeat: 'no-repeat',
                    }}
                >
                    <div className="login-brand">
                        <div className="login-logo">
                            <img src="/images/jlm-logo.png" alt="Logo" />
                        </div>
                        <span>JLM Cozy Cabin</span>
                    </div>

                    {/* Address + contact (matches the signage in your photo) */}
                    <div className="login-intro">
                        <h1>
                            Your Comfortable
                            <br />
                            Living <span>Space Awaits</span>
                        </h1>
                        <p>
                            Manage your rental experience with our comprehensive
                            tenant portal
                        </p>
                    </div>
                </div>

                {/* ===== RIGHT SIDE (unchanged) ===== */}
                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleLogin} noValidate>
                        <a href="/" onClick={handleBackHome} className="back-home-link">
                            <i className="bi bi-arrow-left"></i> Back to Home
                        </a>

                        <h2>Welcome Back!</h2>
                        <p className="login-subtitle">Sign in to access your tenant portal</p>

                        <LoginAlert message={validation.bannerError || flashError} />

                        <LoginField
                            name="email"
                            label="Email Address"
                            type="email"
                            icon="bi bi-envelope"
                            placeholder="name@example.com"
                            autoComplete="email"
                            autoFocus
                            value={data.email}
                            validation={validation}
                        />

                        <LoginField
                            name="password"
                            label="Password"
                            icon="bi bi-lock"
                            placeholder="Enter your password"
                            autoComplete="current-password"
                            revealable
                            value={data.password}
                            validation={validation}
                        />

                        <div className="login-options login-options-end">
                            <Link href="/forgot-password">Forgot password?</Link>
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

                        <button
                            type="button"
                            onClick={handleGoogleLogin}
                            className="google-login-btn"
                            style={{
                                width: '100%',
                                padding: '12px 16px',
                                borderRadius: 10,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 10,
                                fontWeight: 600,
                                background: '#fff',
                                border: '1.5px solid #e2e8f0',
                                cursor: 'pointer',
                            }}
                        >
                            <strong style={{ fontSize: 18, color: '#4285f4' }}>G</strong>
                            Continue with Google
                        </button>

                        <p className="signup-text">
                            Don't have an account? <Link href="/signup">Sign up</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}