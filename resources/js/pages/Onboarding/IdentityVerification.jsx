// resources/js/Pages/Onboarding/IdentityVerification.jsx
import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function IdentityVerification() {
    const { data, setData, post, processing, errors } = useForm({
        id_type: '',
        id_front: null,
        id_back: null,
        verification_agreement: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/identity-verification', {
            forceFormData: true, // required for file uploads
        });
    };

    return (
        <div className="identity-page">
            <Head title="Identity Verification | JLM Cozy Cabin" />

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
                            <label htmlFor="idFront" className="upload-box">
                                <i className="bi bi-cloud-arrow-up"></i>
                                <strong>{data.id_front ? data.id_front.name : 'Click to upload ID front'}</strong>
                                <span>PNG, JPG, JPEG up to 10MB</span>
                            </label>
                            <input
                                type="file"
                                id="idFront"
                                name="id_front"
                                accept=".png,.jpg,.jpeg"
                                hidden
                                onChange={(e) => setData('id_front', e.target.files[0])}
                            />
                            {errors.id_front && <p style={{ color: '#e11d48', fontSize: 12, marginTop: 6 }}>{errors.id_front}</p>}
                        </div>

                        {/* ID BACK */}
                        <div className="identity-form-group">
                            <label>Upload Valid ID (Back) or Selfie with ID</label>
                            <label htmlFor="idBack" className="upload-box">
                                <i className="bi bi-cloud-arrow-up"></i>
                                <strong>{data.id_back ? data.id_back.name : 'Click to upload ID back or selfie'}</strong>
                                <span>PNG, JPG, JPEG up to 10MB (Optional)</span>
                            </label>
                            <input
                                type="file"
                                id="idBack"
                                name="id_back"
                                accept=".png,.jpg,.jpeg"
                                hidden
                                onChange={(e) => setData('id_back', e.target.files[0])}
                            />
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
                            <Link href="/personal-information" className="previous-step-btn">
                                <i className="bi bi-chevron-left"></i>
                                Previous Step
                            </Link>
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