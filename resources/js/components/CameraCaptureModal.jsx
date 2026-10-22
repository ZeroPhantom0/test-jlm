// resources/js/components/CameraCaptureModal.jsx
import React, { useEffect, useRef, useState, useCallback } from 'react';

/**
 * Real webcam capture modal for desktop browsers.
 *
 * Props:
 *  - onCapture(file: File)   → called with a File when the user snaps a photo
 *  - onClose()               → close without capture
 */
export default function CameraCaptureModal({ onCapture, onClose }) {
    const videoRef  = useRef(null);
    const canvasRef = useRef(null);
    const streamRef = useRef(null);

    const [ready, setReady]     = useState(false);
    const [error, setError]     = useState(null);
    const [facing, setFacing]   = useState('user'); // 'user' | 'environment'
    const [busy, setBusy]       = useState(false);

    const stopStream = useCallback(() => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        }
    }, []);

    const startStream = useCallback(async (mode) => {
        stopStream();
        setReady(false);
        setError(null);

        if (!navigator.mediaDevices?.getUserMedia) {
            setError('Your browser does not support camera access.');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: mode } },
                audio: false,
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play().catch(() => {});
            }
            setReady(true);
        } catch (err) {
            const msg =
                err?.name === 'NotAllowedError' ? 'Camera permission was denied.' :
                err?.name === 'NotFoundError'    ? 'No camera found on this device.' :
                err?.name === 'NotReadableError' ? 'Camera is in use by another app.' :
                'Unable to access camera.';
            setError(msg);
        }
    }, [stopStream]);

    useEffect(() => {
        startStream(facing);
        return () => stopStream();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // ESC to close
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    const flip = () => {
        const next = facing === 'user' ? 'environment' : 'user';
        setFacing(next);
        startStream(next);
    };

    const capture = async () => {
        if (!videoRef.current || !canvasRef.current || !ready) return;
        setBusy(true);

        const video  = videoRef.current;
        const canvas = canvasRef.current;
        const w = video.videoWidth;
        const h = video.videoHeight;

        if (!w || !h) {
            setBusy(false);
            setError('Camera is still warming up — try again in a moment.');
            return;
        }

        canvas.width  = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    setBusy(false);
                    setError('Could not capture photo. Please try again.');
                    return;
                }
                const file = new File([blob], `photo-${Date.now()}.jpg`, {
                    type: 'image/jpeg',
                });
                stopStream();
                onCapture(file);
                setBusy(false);
            },
            'image/jpeg',
            0.92
        );
    };

    return (
        <div className="camera-modal-overlay" role="dialog" aria-modal="true">
            <div className="camera-modal">
                <div className="camera-modal-header">
                    <h3>Take a Photo</h3>
                    <button type="button" className="camera-modal-close" onClick={onClose} aria-label="Close">
                        <i className="bi bi-x-lg"></i>
                    </button>
                </div>

                <div className="camera-modal-body">
                    {error ? (
                        <div className="camera-modal-error">
                            <i className="bi bi-camera-video-off"></i>
                            <p>{error}</p>
                            <button type="button" className="camera-modal-retry" onClick={() => startStream(facing)}>
                                Try again
                            </button>
                        </div>
                    ) : (
                        <div className="camera-modal-video-wrap">
                            <video
                                ref={videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="camera-modal-video"
                            />
                            {!ready && (
                                <div className="camera-modal-loading">
                                    <i className="bi bi-arrow-repeat"></i>
                                    <span>Starting camera…</span>
                                </div>
                            )}
                        </div>
                    )}
                    <canvas ref={canvasRef} style={{ display: 'none' }} />
                </div>

                <div className="camera-modal-actions">
                    <button type="button" className="camera-modal-flip" onClick={flip} disabled={!ready || !!error}>
                        <i className="bi bi-arrow-repeat"></i>
                        Flip
                    </button>
                    <button type="button" className="camera-modal-capture" onClick={capture} disabled={!ready || busy || !!error}>
                        <i className="bi bi-camera-fill"></i>
                        {busy ? 'Saving…' : 'Capture'}
                    </button>
                </div>
            </div>
        </div>
    );
}