// resources/js/Pages/Onboarding/PersonalInformation.jsx
import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import CameraCaptureModal from '../../components/CameraCaptureModal';

const DRAFT_KEY = 'jlm_onboarding_personal_draft';

const isMobile = () =>
    typeof navigator !== 'undefined' &&
    /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

export default function PersonalInformation({ user, personalInfo }) {
    let initialDraft = {};
    try {
        const raw = localStorage.getItem(DRAFT_KEY);
        if (raw) initialDraft = JSON.parse(raw) || {};
    } catch (_) {}

    const initial = {
        fullname:         initialDraft.fullname         ?? personalInfo?.full_name        ?? user?.name  ?? '',
        email:            initialDraft.email            ?? personalInfo?.email            ?? user?.email ?? '',
        contact_number:   initialDraft.contact_number   ?? personalInfo?.contact_number   ?? user?.phone ?? '',
        current_address:  initialDraft.current_address  ?? personalInfo?.current_address  ?? '',
        emergency_name:   initialDraft.emergency_name   ?? personalInfo?.emergency_name   ?? '',
        emergency_number: initialDraft.emergency_number ?? personalInfo?.emergency_number ?? '',
        profile_picture:  null,
    };

    const { data, setData, post, processing, errors } = useForm(initial);

    const [previewUrl, setPreviewUrl] = useState(
        personalInfo?.profile_picture
            ? `/storage/${personalInfo.profile_picture}`
            : null
    );
    const [choiceTarget, setChoiceTarget] = useState(null);
    const [showCamera, setShowCamera]     = useState(false);

    const cameraInputRef  = useRef(null);
    const galleryInputRef = useRef(null);

    useEffect(() => {
        const payload = {
            fullname:         data.fullname,
            email:            data.email,
            contact_number:   data.contact_number,
            current_address:  data.current_address,
            emergency_name:   data.emergency_name,
            emergency_number: data.emergency_number,
        };
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
        } catch (_) {}
    }, [
        data.fullname,
        data.email,
        data.contact_number,
        data.current_address,
        data.emergency_name,
        data.emergency_number,
    ]);

    useEffect(() => {
        return () => {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const handleFileSelected = useCallback((file) => {
        if (!file) return;
        setData('profile_picture', file);
        if (previewUrl && previewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(URL.createObjectURL(file));
    }, [previewUrl, setData]);

    const openChooser  = () => setChoiceTarget('profile');
    const closeChooser = () => setChoiceTarget(null);

    const pickCamera = () => {
        closeChooser();
        setTimeout(() => {
            if (isMobile()) cameraInputRef.current?.click();
            else setShowCamera(true);
        }, 60);
    };

    const pickGallery = () => {
        closeChooser();
        setTimeout(() => galleryInputRef.current?.click(), 60);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/personal-information', {
            forceFormData: true,
            onSuccess: () => {
                try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
            },
        });
    };

    return (
        <div className="personal-page">
            <Head title="Personal Information | JLM Cozy Cabin" />

            {choiceTarget && (
                <UploadChoiceModal
                    title="Profile Picture"
                    onCamera={pickCamera}
                    onGallery={pickGallery}
                    onClose={closeChooser}
                />
            )}

            {showCamera && (
                <CameraCaptureModal
                    onCapture={(file) => {
                        setShowCamera(false);
                        handleFileSelected(file);
                    }}
                    onClose={() => setShowCamera(false)}
                />
            )}

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
                                    {previewUrl ? (
                                        <img src={previewUrl} alt="Profile preview" className="profile-picture-preview" />
                                    ) : (
                                        <i className="bi bi-person"></i>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    className="profile-camera-btn"
                                    aria-label="Upload profile picture"
                                    onClick={openChooser}
                                >
                                    <i className="bi bi-camera"></i>
                                </button>

                                <input
                                    ref={cameraInputRef}
                                    type="file"
                                    accept="image/*"
                                    capture="user"
                                    hidden
                                    onChange={(e) => handleFileSelected(e.target.files?.[0])}
                                />
                                <input
                                    ref={galleryInputRef}
                                    type="file"
                                    accept="image/*"
                                    hidden
                                    onChange={(e) => handleFileSelected(e.target.files?.[0])}
                                />
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

export function UploadChoiceModal({ title = 'Upload Photo', onCamera, onGallery, onClose }) {
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    return (
        <div className="upload-modal-overlay" onClick={onClose} role="dialog" aria-modal="true">
            <div className="upload-modal" onClick={(e) => e.stopPropagation()}>
                <button type="button" className="upload-modal-close" onClick={onClose} aria-label="Close">
                    <i className="bi bi-x-lg"></i>
                </button>

                <h3 className="upload-modal-title">{title}</h3>
                <p className="upload-modal-subtitle">Choose how you'd like to add your photo</p>

                <div className="upload-modal-options">
                    <button type="button" className="upload-modal-option" onClick={onCamera}>
                        <span className="upload-modal-option-icon"><i className="bi bi-camera-fill"></i></span>
                        <span className="upload-modal-option-text">
                            <strong>Take a Photo</strong>
                            <small>Use your camera</small>
                        </span>
                    </button>
                    <button type="button" className="upload-modal-option" onClick={onGallery}>
                        <span className="upload-modal-option-icon"><i className="bi bi-image-fill"></i></span>
                        <span className="upload-modal-option-text">
                            <strong>Upload Photo</strong>
                            <small>Choose from your device</small>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    );
}