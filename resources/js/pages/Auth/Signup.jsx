// resources/js/Pages/Auth/Signup.jsx
import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Signup() {
    const [showPw, setShowPw]           = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        fullname: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/signup', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const fieldError = (field) => errors[field] && (
        <div className="signup-field-error">
            <i className="bi bi-exclamation-circle"></i>
            {errors[field]}
        </div>
    );

    return (
        <div className="signup-page signup-page-enter">
            <Head title="Sign Up | JLM Cozy Cabin" />

            <div className="signup-container">
                {/* LEFT */}
                <div className="signup-left">
                    <div className="signup-brand">
                        <div className="signup-logo">
                            <img src="/images/jlm-logo.png" alt="JLM Cozy Cabin Logo" />
                        </div>
                        <span>JLM Cozy Cabin</span>
                    </div>

                    <div className="signup-intro">
                        <h1>
                            Your Comfortable
                            <br />
                            Living <span>Space Awaits</span>
                        </h1>
                        <p>Manage your rental experience with our comprehensive tenant portal</p>

                        <div className="signup-benefits">
                            <div className="signup-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Submit maintenance requests instantly</p>
                            </div>
                            <div className="signup-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Track payments and billing history</p>
                            </div>
                            <div className="signup-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Browse available rooms virtually</p>
                            </div>
                            <div className="signup-benefit">
                                <span><i className="bi bi-check"></i></span>
                                <p>Stay updated with announcements</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* RIGHT */}
                <div className="signup-right">
                    <form className="signup-form-container" onSubmit={handleSubmit}>
                        <h2>Hello, There!</h2>
                        <p className="signup-subtitle">
                            Sign up to reserve a unit and access tenant services
                        </p>

                        {/* FULL NAME */}
                        <div className="signup-form-group">
                            <label htmlFor="signupFullName">Full Name *</label>
                            <div className="signup-input-wrapper">
                                <span className="signup-input-icon"><i className="bi bi-person"></i></span>
                                <input
                                    type="text"
                                    id="signupFullName"
                                    name="fullname"
                                    placeholder="Juan Dela Cruz"
                                    value={data.fullname}
                                    onChange={(e) => setData('fullname', e.target.value)}
                                    autoFocus
                                />
                            </div>
                            {fieldError('fullname')}
                        </div>

                        {/* EMAIL + PHONE */}
                        <div className="signup-form-row">
                            <div className="signup-form-group">
                                <label htmlFor="signupEmail">Email Address *</label>
                                <div className="signup-input-wrapper">
                                    <span className="signup-input-icon"><i className="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        id="signupEmail"
                                        name="email"
                                        placeholder="name@example.com"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        autoComplete="email"
                                    />
                                </div>
                                {fieldError('email')}
                            </div>
                            <div className="signup-form-group">
                                <label htmlFor="signupPhone">Phone Number *</label>
                                <div className="signup-input-wrapper">
                                    <span className="signup-input-icon"><i className="bi bi-telephone"></i></span>
                                    <input
                                        type="tel"
                                        id="signupPhone"
                                        name="phone"
                                        placeholder="+63 912 345 6789"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        autoComplete="tel"
                                    />
                                </div>
                                {fieldError('phone')}
                            </div>
                        </div>

                        {/* PASSWORDS */}
                        <div className="signup-form-row">
                            <div className="signup-form-group">
                                <label htmlFor="signupPassword">Password *</label>
                                <div className="signup-input-wrapper">
                                    <span className="signup-input-icon"><i className="bi bi-lock"></i></span>
                                    <input
                                        type={showPw ? 'text' : 'password'}
                                        id="signupPassword"
                                        name="password"
                                        placeholder="Create password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        autoComplete="new-password"
                                    />
                                    <button
                                        type="button"
                                        className="signup-password-toggle"
                                        onClick={() => setShowPw(!showPw)}
                                        aria-label={showPw ? 'Hide password' : 'Show password'}
                                    >
                                        <i className={showPw ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                    </button>
                                </div>
                                {fieldError('password')}
                            </div>
                            <div className="signup-form-group">
                                <label htmlFor="signupConfirmPassword">Confirm Password *</label>
                                <div className="signup-input-wrapper">
                                    <span className="signup-input-icon"><i className="bi bi-lock"></i></span>
                                    <input
                                        type={showConfirm ? 'text' : 'password'}
                                        id="signupConfirmPassword"
                                        name="password_confirmation"
                                        placeholder="Confirm password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        autoComplete="new-password"
                                    />
                                    <button
                                        type="button"
                                        className="signup-password-toggle"
                                        onClick={() => setShowConfirm(!showConfirm)}
                                        aria-label={showConfirm ? 'Hide password' : 'Show password'}
                                    >
                                        <i className={showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                    </button>
                                </div>
                                {fieldError('password_confirmation')}
                            </div>
                        </div>

                        {/* TERMS */}
                        <label className="signup-terms">
                            <input
                                type="checkbox"
                                name="terms"
                                checked={data.terms}
                                onChange={(e) => setData('terms', e.target.checked)}
                            />
                            <span>
                                I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>
                            </span>
                        </label>
                        {fieldError('terms')}

                        {/* SIGNUP */}
                        <button
                            type="submit"
                            className="signup-btn"
                            disabled={processing}
                            style={{ opacity: processing ? 0.7 : 1, cursor: processing ? 'wait' : 'pointer' }}
                        >
                            {processing ? (
                                <>
                                    <i className="bi bi-arrow-repeat" style={{ animation: 'spin 1s linear infinite', marginRight: 8 }}></i>
                                    Creating account...
                                </>
                            ) : (
                                'Sign Up'
                            )}
                        </button>

                        <p className="login-link-text">
                            Already have an account? <Link href="/login">Log in</Link>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    );
}