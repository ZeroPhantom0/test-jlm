// resources/js/components/SplashScreen.jsx
import React, { useEffect, useState } from 'react';

/**
 * SplashScreen — branded loading overlay
 *
 * Props:
 *  - duration: how long the splash stays visible (ms). Default 1800.
 *  - onComplete: callback fired when the splash finishes.
 *  - message: optional tagline shown below the logo.
 */
export default function SplashScreen({
    duration = 1800,
    onComplete,
    message = 'Your cozy home awaits',
}) {
    const [progress, setProgress] = useState(0);
    const [exiting, setExiting] = useState(false);

    useEffect(() => {
        // Animate the progress bar over `duration`
        const start = performance.now();
        let raf;

        const tick = (now) => {
            const elapsed = now - start;
            const pct = Math.min(100, (elapsed / duration) * 100);
            setProgress(pct);
            if (pct < 100) {
                raf = requestAnimationFrame(tick);
            }
        };
        raf = requestAnimationFrame(tick);

        // Start exit slightly before onComplete so the fade feels seamless
        const exitTimer = setTimeout(() => setExiting(true), duration - 400);
        const doneTimer = setTimeout(() => {
            if (onComplete) onComplete();
        }, duration);

        return () => {
            cancelAnimationFrame(raf);
            clearTimeout(exitTimer);
            clearTimeout(doneTimer);
        };
    }, [duration, onComplete]);

    return (
        <div className={`splash-overlay ${exiting ? 'splash-exit' : ''}`}>
            <div className="splash-content">
                {/* Animated logo ring */}
                <div className="splash-logo-wrap">
                    <div className="splash-ring splash-ring-outer" />
                    <div className="splash-ring splash-ring-middle" />
                    <div className="splash-logo">
                        <img
                            src="/images/jlm-logo.png"
                            alt="JLM Cozy Cabin"
                            onError={(e) => { e.currentTarget.style.display = 'none'; }}
                        />
                        <span className="splash-logo-fallback">JLM</span>
                    </div>
                </div>

                {/* Brand text */}
                <h1 className="splash-brand">
                    JLM <span>Cozy Cabin</span>
                </h1>
                <p className="splash-message">{message}</p>

                {/* Progress bar */}
                <div className="splash-progress-track">
                    <div
                        className="splash-progress-fill"
                        style={{ width: `${progress}%` }}
                    />
                </div>
                <p className="splash-percent">{Math.round(progress)}%</p>
            </div>

            {/* Decorative floating dots */}
            <div className="splash-decor splash-decor-1" />
            <div className="splash-decor splash-decor-2" />
            <div className="splash-decor splash-decor-3" />
            <div className="splash-decor splash-decor-4" />
        </div>
    );
}