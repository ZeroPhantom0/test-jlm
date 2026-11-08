// resources/js/pages/Auth/AdminLogin.jsx
import React, { useState } from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';

export default function AdminLogin() {
    const { props } = usePage();
    const flashError = props.flash?.error;
    const [showPassword, setShowPassword] = useState(false);
    const [localErrors, setLocalErrors] = useState({});

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const handleLogin = (e) => {
        e.preventDefault();
        
        const newErrors = {};
        if (!data.email.trim()) {
            newErrors.email = 'Please enter your email address.';
        } else if (!/\S+@\S+\.\S+/.test(data.email)) {
            newErrors.email = 'Please enter a valid email address.';
        }
        
        if (!data.password) {
            newErrors.password = 'Please enter your password.';
        }

        if (Object.keys(newErrors).length > 0) {
            setLocalErrors(newErrors);
            return;
        }

        setLocalErrors({});
        post('/admin/login', { onFinish: () => reset('password') });
    };

    const handleBackHome = (e) => {
        e.preventDefault();
        router.visit('/', { replace: true });
    };

    const displayErrors = { ...errors, ...localErrors };

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

                        {flashError && (
                            <div className="login-global-error">
                                <i className="bi bi-exclamation-circle"></i>
                                {flashError}
                            </div>
                        )}

                        <div className="login-form-group">
                            <label>Email Address</label>
                            <div className={`login-input-wrapper ${displayErrors.email ? 'border-rose-500' : ''}`}>
                                <span className="login-input-icon"><i className="bi bi-envelope"></i></span>
                                <input 
                                    type="email" 
                                    placeholder="admin@jlmcozycabin.com" 
                                    value={data.email} 
                                    onChange={(e) => {
                                        setData('email', e.target.value);
                                        if (localErrors.email) setLocalErrors({ ...localErrors, email: null });
                                    }} 
                                    autoComplete="email" 
                                    autoFocus 
                                    className={displayErrors.email ? 'text-rose-600' : ''}
                                />
                            </div>
                            {displayErrors.email && (
                                <div className="text-rose-500 text-[12px] mt-1.5 flex items-center gap-1">
                                    <i className="bi bi-exclamation-circle"></i>
                                    {displayErrors.email}
                                </div>
                            )}
                        </div>

                        <div className="login-form-group">
                            <label>Password</label>
                            <div className={`login-input-wrapper ${displayErrors.password ? 'border-rose-500' : ''}`}>
                                <span className="login-input-icon"><i className="bi bi-lock"></i></span>
                                <input 
                                    type={showPassword ? 'text' : 'password'} 
                                    placeholder="Enter your password" 
                                    value={data.password} 
                                    onChange={(e) => {
                                        setData('password', e.target.value);
                                        if (localErrors.password) setLocalErrors({ ...localErrors, password: null });
                                    }} 
                                    autoComplete="current-password" 
                                    className={displayErrors.password ? 'text-rose-600' : ''}
                                />
                                <button type="button" className="password-toggle" onClick={() => setShowPassword(!showPassword)}>
                                    <i className={showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                </button>
                            </div>
                            {displayErrors.password && (
                                <div className="text-rose-500 text-[12px] mt-1.5 flex items-center gap-1">
                                    <i className="bi bi-exclamation-circle"></i>
                                    {displayErrors.password}
                                </div>
                            )}
                        </div>

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