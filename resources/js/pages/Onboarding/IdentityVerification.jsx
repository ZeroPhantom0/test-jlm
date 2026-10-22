// resources/js/Pages/Onboarding/IdentityVerification.jsx
import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { UploadChoiceModal } from './PersonalInformation';
import CameraCaptureModal from '../../components/CameraCaptureModal';

const DRAFT_KEY = 'jlm_onboarding_identity_draft';

const isMobile = () =>
    typeof navigator !== 'undefined' &&
    /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

export default function IdentityVerification({ identity }) {
    let initialDraft = {};
    try {
        const raw = localStorage.getItem(DRAFT_KEY);
        if (raw) initialDraft = JSON.parse(raw) || {};
    } catch (_) {}

    const initial = {
        id_type:                initialDraft.id_type                ?? identity?.id_type                ?? '',
        id_front:               null,
        id_back:                null,
        verification_agreement: initialDraft.verification_agreement ?? false,
    };

    const { data, setData, post, processing, errors } = useForm(initial);

    const [frontPreview, setFrontPreview] = useState(
        identity?.id_front ? `/storage/${identity.id_front}` : null
    );
    const [backPreview, setBackPreview] = useState(
        identity?.id_back ? `/storage/${identity.id_back}` : null
    );
    const [choiceTarget, setChoiceTarget] = useState(null);
    const [cameraTarget, setCameraTarget] = useState(null);

    const frontCameraRef  = useRef(null);
    const frontGalleryRef = useRef(null);
    const backCameraRef   = useRef(null);
    const backGalleryRef  = useRef(null);

    useEffect(() => {
        const payload = {
            id_type: data.id_type,
            verification_agreement: data.verification_agreement,
        };
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(payload));
        } catch (_) {}
    }, [data.id_type, data.verification_agreement]);

    useEffect(() => {
        return () => {
            if (frontPreview && frontPreview.startsWith('blob:')) URL.revokeObjectURL(frontPreview);
            if (backPreview  && backPreview.startsWith('blob:'))  URL.revokeObjectURL(backPreview);
        };
    }, [frontPreview, backPreview]);

    const handleFrontSelected = useCallback((file) => {
        if (!file) return;
        setData('id_front', file);
        if (frontPreview && frontPreview.startsWith('blob:')) URL.revokeObjectURL(frontPreview);
        setFrontPreview(URL.createObjectURL(file));
    }, [frontPreview, setData]);

    const handleBackSelected = useCallback((file) => {
        if (!file) return;
        setData('id_back', file);
        if (backPreview && backPreview.startsWith('blob:')) URL.revokeObjectURL(backPreview);
        setBackPreview(URL.createObjectURL(file));
    }, [backPreview, setData]);

    const openChooser  = (target) => setChoiceTarget(target);
    const closeChooser = () => setChoiceTarget(null);

    const runCamera = () => {
        const target = choiceTarget;
        closeChooser();
        setTimeout(() => {
            if (target === 'front') {
                if (isMobile()) frontCameraRef.current?.click();
                else setCameraTarget('front');
            }
            if (target === 'back') {
                if (isMobile()) backCameraRef.current?.click();
                else setCameraTarget('back');
            }
        }, 60);
    };

    const runGallery = () => {
        const target = choiceTarget;
        closeChooser();
        setTimeout(() => {
            if (target === 'front') frontGalleryRef.current?.click();
            if (target === 'back')  backGalleryRef.current?.click();
        }, 60);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/identity-verification', {
            forceFormData: true,
            onSuccess: () => {
                try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
            },
        });
    };

    const goBackToPersonal = (e) => {
        e.preventDefault();
        router.get('/personal-information');
    };

    return (
        <div className="identity-page">
            <Head title="Identity Verification | JLM Cozy Cabin" />

            {choiceTarget && (
                <UploadChoiceModal
                    title={choiceTarget === 'front' ? 'Upload ID (Front)' : 'Upload ID (Back / Selfie)'}
                    onCamera={runCamera}
                    onGallery={runGallery}
                    onClose={closeChooser}
                />
            )}

            {cameraTarget && (
                <CameraCaptureModal
                    onCapture={(file) => {
                        const target = cameraTarget;
                        setCameraTarget(null);
                        if (target === 'front') handleFrontSelected(file);
                        if (target === 'back')  handleBackSelected(file);
                    }}
                    onClose={() => setCameraTarget(null)}
                />
            )}

            <div className="identity-container">
                {/* LEFT */}
                <div className="identity-left">
                    <div className="identity-brand">
                        <div className="identity-logo">
                            <img src="/images/JLM Cozy cabin.jpg" alt="JLM Cozy Cabin Logo" />
                        </div>
                        <div>
                            <strong>JLM Cozy Cabin</strong>
                            <span>Complete Your Profile</span>
                        </div>
                    </div>

                    <div className="identity-intro">
                        <h1>Almost There!</h1>
                        <p>
                            Complete your profile to access your tenant
                            portal and start managing your rental
                            experience.
                        </p>

                        <div className="identity-step completed">
                            <div className="step-icon"><i className="bi bi-check"></i></div>
                            <div className="step-content">
                                <strong>Step 1</strong>
                                <span>Personal Information</span>
                            </div>
                        </div>

                        <div className="identity-step active">
                            <div className="step-icon"><i className="bi bi-file-earmark-text"></i></div>
                            <div className="step-content">
                                <strong>Step 2</strong>
                                <span>Identity Verification</span>
                            </div>
                        </div>
                    </div>

                    <div className="identity-help">
                        <div className="help-line"></div>
                        <p>Need help? Contact us at <strong>support@jlmcozycabin.ph</strong></p>
                    </div>
                </div>

                {/* RIGHT */}
                <div className="identity-right">
                    <form className="identity-form-container" onSubmit={handleSubmit}>
                        <h2>Identity Verification</h2>
                        <p className="identity-subtitle">
                            Upload a valid government-issued ID for verification
                        </p>

                        {/* ID TYPE */}
                        <div className="identity-form-group">
                            <label htmlFor="idType">Valid ID Type *</label>
                            <div className="identity-select-wrapper">
                                <select
                                    id="idType"
                                    name="id_type"
                                    value={data.id_type}
                                    onChange={(e) => setData('id_type', e.target.value)}
                                >
                                    <option value="">Choose ID Type</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers-license">Driver's License</option>
                                    <option value="national-id">National ID</option>
                                    <option value="sss">SSS ID</option>
                                    <option value="philhealth">PhilHealth ID</option>
                                    <option value="umid">UMID</option>
                                    <option value="postal-id">Postal ID</option>
                                    <option value="other">Other Government-Issued ID</option>
                                </select>
                                <i className="bi bi-chevron-down"></i>
                            </div>
                            {errors.id_type && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.id_type}</p>}
                        </div>

                        {/* ID FRONT */}
                        <div className="identity-form-group">
                            <label>Upload Valid ID (Front) *</label>
                            <button
                                type="button"
                                className="upload-box"
                                onClick={() => openChooser('front')}
                            >
                                {frontPreview ? (
                                    <img src={frontPreview} alt="ID front preview" className="upload-preview-img" />
                                ) : (
                                    <>
                                        <i className="bi bi-cloud-arrow-up"></i>
                                        <strong>{data.id_front ? data.id_front.name : 'Click to upload ID front'}</strong>
                                        <span>PNG, JPG, JPEG up to 10MB</span>
                                    </>
                                )}
                            </button>

                            <input
                                ref={frontCameraRef}
                                type="file"
                                accept="image/*"
                                capture="environment"
                                hidden
                                onChange={(e) => handleFrontSelected(e.target.files?.[0])}
                            />
                            <input
                                ref={frontGalleryRef}
                                type="file"
                                accept=".png,.jpg,.jpeg,image/*"
                                hidden
                                onChange={(e) => handleFrontSelected(e.target.files?.[0])}
                            />

                            {data.id_front && (
                                <p className="upload-filename">
                                    <i className="bi bi-check-circle-fill"></i> {data.id_front.name}
                                </p>
                            )}
                            {errors.id_front && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.id_front}</p>}
                        </div>

                        {/* ID BACK */}
                        <div className="identity-form-group">
                            <label>Upload Valid ID (Back) or Selfie with ID</label>
                            <button
                                type="button"
                                className="upload-box"
                                onClick={() => openChooser('back')}
                            >
                                {backPreview ? (
                                    <img src={backPreview} alt="ID back preview" className="upload-preview-img" />
                                ) : (
                                    <>
                                        <i className="bi bi-cloud-arrow-up"></i>
                                        <strong>{data.id_back ? data.id_back.name : 'Click to upload ID back or selfie'}</strong>
                                        <span>PNG, JPG, JPEG up to 10MB (Optional)</span>
                                    </>
                                )}
                            </button>

                            <input
                                ref={backCameraRef}
                                type="file"
                                accept="image/*"
                                capture="user"
                                hidden
                                onChange={(e) => handleBackSelected(e.target.files?.[0])}
                            />
                            <input
                                ref={backGalleryRef}
                                type="file"
                                accept=".png,.jpg,.jpeg,image/*"
                                hidden
                                onChange={(e) => handleBackSelected(e.target.files?.[0])}
                            />

                            {data.id_back && (
                                <p className="upload-filename">
                                    <i className="bi bi-check-circle-fill"></i> {data.id_back.name}
                                </p>
                            )}
                            {errors.id_back && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.id_back}</p>}
                        </div>

                        {/* SECURITY NOTICE */}
                        <div className="security-notice">
                            <i className="bi bi-exclamation-circle"></i>
                            <p>
                                Your documents are encrypted and securely stored.
                                We only use them for identity verification and
                                never share your information with third parties.
                            </p>
                        </div>

                        {/* AGREEMENT */}
                        <label className="verification-agreement">
                            <input
                                type="checkbox"
                                name="verification_agreement"
                                checked={data.verification_agreement}
                                onChange={(e) => setData('verification_agreement', e.target.checked)}
                            />
                            <span>
                                I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>.
                                I understand that my information will be used for verification purposes only.
                            </span>
                        </label>
                        {errors.verification_agreement && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.verification_agreement}</p>}

                        {/* ACTIONS */}
                        <div className="identity-actions">
                            <button
                                type="button"
                                className="previous-step-btn"
                                onClick={goBackToPersonal}
                            >
                                <i className="bi bi-chevron-left"></i>
                                Previous Step
                            </button>
                            <button
                                type="submit"
                                className="submit-finish-btn"
                                disabled={processing}
                                style={{ opacity: processing ? 0.7 : 1, cursor: processing ? 'wait' : 'pointer' }}
                            >
                                {processing ? 'Uploading...' : 'Submit & Finish'}
                                <i className="bi bi-check"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}