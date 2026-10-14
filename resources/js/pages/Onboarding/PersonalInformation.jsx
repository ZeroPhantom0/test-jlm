// resources/js/Pages/Onboarding/PersonalInformation.jsx
import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function PersonalInformation({ user }) {
    const { data, setData, post, processing, errors } = useForm({
        fullname: user?.name || '',
        email: user?.email || '',
        contact_number: user?.phone || '',
        current_address: '',
        emergency_name: '',
        emergency_number: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/personal-information');
    };

    return (
        <div className="personal-page">
            <Head title="Personal Information | JLM Cozy Cabin" />

            <div className="personal-container">
                {/* LEFT */}
                <div className="personal-left">
                    <div className="personal-brand">
                        <div className="personal-logo">
                            <img src="/images/JLM Cozy cabin.jpg" alt="JLM Cozy Cabin Logo" />
                        </div>
                        <div>
                            <strong>JLM Cozy Cabin</strong>
                            <span>Complete Your Profile</span>
                        </div>
                    </div>

                    <div className="personal-intro">
                        <h1>Almost There!</h1>
                        <p>
                            Complete your profile to access your tenant
                            portal and start managing your rental
                            experience.
                        </p>

                        <div className="personal-step active">
                            <div className="step-icon"><i className="bi bi-person"></i></div>
                            <div className="step-content">
                                <strong>Step 1</strong>
                                <span>Personal Information</span>
                            </div>
                        </div>

                        <div className="personal-step">
                            <div className="step-icon"><i className="bi bi-file-earmark-text"></i></div>
                            <div className="step-content">
                                <strong>Step 2</strong>
                                <span>Identity Verification</span>
                            </div>
                        </div>
                    </div>

                    <div className="personal-help">
                        <div className="help-line"></div>
                        <p>Need help? Contact us at <strong>support@jlmcozycabin.ph</strong></p>
                    </div>
                </div>

                {/* RIGHT */}
                <div className="personal-right">
                    <form className="personal-form-container" onSubmit={handleSubmit}>
                        <h2>Personal Information</h2>
                        <p className="personal-subtitle">Tell us about yourself to get started</p>

                        {/* PROFILE PICTURE */}
                        <div className="profile-picture-section">
                            <div className="profile-picture-wrapper">
                                <div className="profile-picture-placeholder">
                                    <i className="bi bi-person"></i>
                                </div>
                                <label
                                    htmlFor="profilePicture"
                                    className="profile-camera-btn"
                                    aria-label="Upload profile picture"
                                >
                                    <i className="bi bi-camera"></i>
                                </label>
                                <input type="file" id="profilePicture" name="profile_picture" accept="image/*" hidden />
                            </div>
                            <div className="profile-picture-info">
                                <h3>Profile Picture</h3>
                                <p>Upload a clear photo of yourself (optional)</p>
                            </div>
                        </div>

                        <div className="personal-divider"></div>

                        {/* FULL NAME */}
                        <div className="personal-form-group">
                            <label htmlFor="fullName">Full Name *</label>
                            <div className="personal-input-wrapper">
                                <span className="personal-input-icon"><i className="bi bi-person"></i></span>
                                <input
                                    type="text"
                                    id="fullName"
                                    name="fullname"
                                    placeholder="Juan Dela Cruz"
                                    value={data.fullname}
                                    onChange={(e) => setData('fullname', e.target.value)}
                                />
                            </div>
                            {errors.fullname && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.fullname}</p>}
                        </div>

                        {/* EMAIL + CONTACT */}
                        <div className="personal-form-row">
                            <div className="personal-form-group">
                                <label htmlFor="email">Email Address *</label>
                                <div className="personal-input-wrapper">
                                    <span className="personal-input-icon"><i className="bi bi-envelope"></i></span>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        placeholder="juan@example.com"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                </div>
                                {errors.email && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.email}</p>}
                            </div>
                            <div className="personal-form-group">
                                <label htmlFor="contactNumber">Contact Number *</label>
                                <div className="personal-input-wrapper">
                                    <span className="personal-input-icon"><i className="bi bi-telephone"></i></span>
                                    <input
                                        type="tel"
                                        id="contactNumber"
                                        name="contact_number"
                                        placeholder="+63 912 345 6789"
                                        value={data.contact_number}
                                        onChange={(e) => setData('contact_number', e.target.value)}
                                    />
                                </div>
                                {errors.contact_number && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.contact_number}</p>}
                            </div>
                        </div>

                        {/* ADDRESS */}
                        <div className="personal-form-group">
                            <label htmlFor="currentAddress">Current Address</label>
                            <div className="personal-input-wrapper">
                                <span className="personal-input-icon"><i className="bi bi-geo-alt"></i></span>
                                <textarea
                                    id="currentAddress"
                                    name="current_address"
                                    placeholder="123 Street Name, Barangay, City, Province"
                                    value={data.current_address}
                                    onChange={(e) => setData('current_address', e.target.value)}
                                />
                            </div>
                        </div>

                        {/* EMERGENCY */}
                        <div className="emergency-contact-box">
                            <h3><i className="bi bi-exclamation-circle"></i> Emergency Contact Information</h3>
                            <div className="emergency-form-row">
                                <div className="personal-form-group">
                                    <label htmlFor="emergencyName">Contact Name *</label>
                                    <div className="personal-input-wrapper">
                                        <input
                                            type="text"
                                            id="emergencyName"
                                            name="emergency_name"
                                            placeholder="Maria Dela Cruz"
                                            value={data.emergency_name}
                                            onChange={(e) => setData('emergency_name', e.target.value)}
                                        />
                                    </div>
                                    {errors.emergency_name && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.emergency_name}</p>}
                                </div>
                                <div className="personal-form-group">
                                    <label htmlFor="emergencyNumber">Contact Number *</label>
                                    <div className="personal-input-wrapper">
                                        <input
                                            type="tel"
                                            id="emergencyNumber"
                                            name="emergency_number"
                                            placeholder="+63 998 765 4321"
                                            value={data.emergency_number}
                                            onChange={(e) => setData('emergency_number', e.target.value)}
                                        />
                                    </div>
                                    {errors.emergency_number && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.emergency_number}</p>}
                                </div>
                            </div>
                        </div>

                        {/* ACTIONS */}
                        <div className="personal-actions">
                            <Link href="/login" className="back-login-btn">
                                <i className="bi bi-chevron-left"></i>
                                Back to Login
                            </Link>
                            <button
                                type="submit"
                                className="next-step-btn"
                                disabled={processing}
                                style={{ opacity: processing ? 0.7 : 1, cursor: processing ? 'wait' : 'pointer' }}
                            >
                                {processing ? 'Saving...' : 'Next Step'}
                                <i className="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}