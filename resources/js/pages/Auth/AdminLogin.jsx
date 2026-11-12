// resources/js/pages/Auth/AdminLogin.jsx
import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { LoginAlert, LoginField, useLoginValidation } from '../../components/auth/LoginForm';

export default function AdminLogin() {
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

        post('/admin/login', {
            preserveScroll: true,
            onFinish: () => reset('password'),
        });
    };

    const handleBackHome = (e) => {
        e.preventDefault();
        router.visit('/', { replace: true });
    };

    return (
        <div className="login-page login-page-enter">
            <Head title="Admin Login | JLM Cozy Cabin" />

            <div className="login-container">
                {/* LEFT — Matches Tenant Login format but Admin content */}
                <div className="login-left">
                    <div className="login-brand">
                        <div className="login-logo">
                            <img src="/images/jlm-logo.png" alt="Logo" />
                        </div>
                        <span>JLM Cozy Cabin</span>
                    </div>

                    {/* Address / contact block under the brand */}
                    <div className="login-intro">
                        <h1>
                            Administrator
                            <br />
                            Access <span>Portal</span>
                        </h1>
                        <p>Manage your property, tenants, and operations securely.</p>
                    </div>
                </div>

                {/* RIGHT — Form */}
                <div className="login-right">
                    <form className="login-form-container" onSubmit={handleLogin} noValidate>
                        <a href="/" onClick={handleBackHome} className="back-home-link">
                            <i className="bi bi-arrow-left"></i> Back to Home
                        </a>
                        
                        <h2>Admin Sign In</h2>
                        <p className="login-subtitle">Enter your credentials to access the admin dashboard</p>

                        <LoginAlert message={validation.bannerError || flashError} />

                        <LoginField
                            name="email"
                            label="Email Address"
                            type="email"
                            icon="bi bi-envelope"
                            placeholder="admin@jlmcozycabin.com"
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

                        <button 
                            type="submit" 
                            className="login-submit-btn" 
                            disabled={processing} 
                            style={{ opacity: processing ? 0.7 : 1, marginTop: '24px' }}
                        >
                            {processing ? (
                                <>
                                    <i className="bi bi-arrow-repeat" style={{ animation: 'spin 1s linear infinite' }}></i> 
                                    Signing in...
                                </>
                            ) : (
                                'Log In as Admin'
                            )}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}