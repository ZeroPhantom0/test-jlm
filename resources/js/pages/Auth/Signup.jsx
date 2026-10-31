// resources/js/pages/Auth/Signup.jsx
import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import PrivacyModal from './Modals/PrivacyModal';
import TermsModal from './Modals/TermsModal';

export default function Signup() {
    const [showPw, setShowPw]           = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    // Modal States
    const [showPrivacyModal, setShowPrivacyModal] = useState(false);
    const [showTermsModal, setShowTermsModal]     = useState(false);

    // Track which fields the user has interacted with
    const [touched, setTouched] = useState({});
    // Track whether user attempted submit at least once
    const [submitAttempted, setSubmitAttempted] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        fullname: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    /* ============================================================
       CLIENT-SIDE VALIDATION RULES
       ============================================================ */
    const clientErrors = useMemo(() => {
        const e = {};

        // Full name
        if (data.fullname) {
            if (data.fullname.trim().length < 3) {
                e.fullname = 'Please enter your full name (at least 3 characters).';
            } else if (data.fullname.trim().length > 100) {
                e.fullname = 'Name is too long (maximum 100 characters).';
            }
        }

        // Email
        if (data.email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
            if (!emailRegex.test(data.email)) {
                e.email = 'Please enter a valid email (e.g. name@example.com).';
            }
        }

        // Phone
        if (data.phone) {
            const digits = data.phone.replace(/\D/g, '');
            if (digits.length < 10) {
                e.phone = 'Phone number looks too short. Include the area/country code.';
            } else if (digits.length > 15) {
                e.phone = 'Phone number is too long. Please double-check.';
            }
        }

        // Password
        if (data.password) {
            if (data.password.length < 8) {
                e.password = 'Password must be at least 8 characters.';
            } else if (
                !/[A-Z]/.test(data.password) ||
                !/[a-z]/.test(data.password) ||
                !/[0-9]/.test(data.password)
            ) {
                e.password = 'Use uppercase, lowercase, and a number for a strong password.';
            }
        }

        // Confirm password
        if (data.password_confirmation) {
            if (data.password_confirmation !== data.password) {
                e.password_confirmation = 'Passwords do not match.';
            }
        }

        // Terms — required
        if (!data.terms) {
            e.terms = 'Please accept the Privacy Policy to continue.';
        }

        return e;
    }, [data]);

    /* ============================================================
       HELPERS
       ============================================================ */
    const fieldError = (field) => {
        const message = clientErrors[field] || errors[field];
        if (!message) return null;
        if (!touched[field] && !submitAttempted) return null;

        return (
            <div className="signup-field-error">
                <i className="bi bi-exclamation-circle"></i>
                {message}
            </div>
        );
    };

    const fieldValid = (field) => {
        if (!touched[field] && !submitAttempted) return false;
        if (clientErrors[field] || errors[field]) return false;
        if (!data[field]) return false;
        return (
            <div className="signup-field-valid">
                <i className="bi bi-check-circle-fill"></i>
            </div>
        );
    };

    const handleBlur = (field) => {
        setTouched((t) => ({ ...t, [field]: true }));
    };

    /* ============================================================
       SUBMIT
       ============================================================ */
    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitAttempted(true);

        // Block if any client-side validation fails (includes terms)
        if (Object.keys(clientErrors).length > 0) {
            return;
        }

        setShowTermsModal(true);
    };

    const confirmTermsAndSubmit = () => {
        setShowTermsModal(false);
        post('/signup', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    const handleLoginNav = (e) => {
        e.preventDefault();
        router.visit('/login');
    };

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
                    <form className="signup-form-container" onSubmit={handleSubmit} noValidate>
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
                                    onBlur={() => handleBlur('fullname')}
                                    autoFocus
                                    aria-invalid={!!(touched.fullname && (clientErrors.fullname || errors.fullname))}
                                />
                                {fieldValid('fullname')}
                            </div>
                            {touched.fullname && !clientErrors.fullname && !errors.fullname && (
                                <div className="signup-field-hint">
                                    <i className="bi bi-info-circle"></i>
                                    Use your real name as it appears on your ID.
                                </div>
                            )}
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
                                        onBlur={() => handleBlur('email')}
                                        autoComplete="email"
                                        aria-invalid={!!(touched.email && (clientErrors.email || errors.email))}
                                    />
                                    {fieldValid('email')}
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
                                        onBlur={() => handleBlur('phone')}
                                        autoComplete="tel"
                                        aria-invalid={!!(touched.phone && (clientErrors.phone || errors.phone))}
                                    />
                                    {fieldValid('phone')}
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
                                        onBlur={() => handleBlur('password')}
                                        autoComplete="new-password"
                                        aria-invalid={!!(touched.password && (clientErrors.password || errors.password))}
                                    />
                                    <button
                                        type="button"
                                        className="signup-password-toggle"
                                        onClick={() => setShowPw(!showPw)}
                                        aria-label={showPw ? 'Hide password' : 'Show password'}
                                    >
                                        <i className={showPw ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                    </button>
                                    {fieldValid('password')}
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
                                        onBlur={() => handleBlur('password_confirmation')}
                                        autoComplete="new-password"
                                        aria-invalid={!!(touched.password_confirmation && (clientErrors.password_confirmation || errors.password_confirmation))}
                                    />
                                    <button
                                        type="button"
                                        className="signup-password-toggle"
                                        onClick={() => setShowConfirm(!showConfirm)}
                                        aria-label={showConfirm ? 'Hide password' : 'Show password'}
                                    >
                                        <i className={showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'}></i>
                                    </button>
                                    {fieldValid('password_confirmation')}
                                </div>
                                {fieldError('password_confirmation')}
                            </div>
                        </div>

                        {/* TERMS */}
                        <label
                            className={`signup-terms ${
                                submitAttempted && !data.terms ? 'signup-terms-invalid' : ''
                            }`}
                        >
                            <input
                                type="checkbox"
                                name="terms"
                                checked={data.terms}
                                onChange={(e) => {
                                    setData('terms', e.target.checked);
                                    handleBlur('terms');
                                }}
                                aria-invalid={!!(submitAttempted && !data.terms)}
                            />
                            <span>
                                I agree to the{' '}
                                <button
                                    type="button"
                                    onClick={() => setShowPrivacyModal(true)}
                                    className="text-link"
                                >
                                    Privacy Policy
                                </button>
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
                            Already have an account?{" "}
                            <a href="/login" onClick={handleLoginNav}>
                                Log in
                            </a>
                        </p>
                    </form>
                </div>
            </div>

            {/* Modals */}
            <PrivacyModal isOpen={showPrivacyModal} onClose={() => setShowPrivacyModal(false)} />
            <TermsModal
                isOpen={showTermsModal}
                onClose={() => setShowTermsModal(false)}
                onConfirm={confirmTermsAndSubmit}
            />
        </div>
    );
}