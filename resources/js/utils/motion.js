// resources/js/utils/motion.js
// Small, dependency-free motion helpers (no framer-motion / canvas-confetti).
import { useEffect, useRef, useState } from "react";

export const prefersReducedMotion = () =>
    typeof window !== "undefined" &&
    window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;

/** true once the element has scrolled into view (fires once). */
export function useInView(options = { threshold: 0.25 }) {
    const ref = useRef(null);
    const [inView, setInView] = useState(false);

    useEffect(() => {
        const el = ref.current;
        if (!el || inView) return;
        if (!("IntersectionObserver" in window)) {
            setInView(true);
            return;
        }
        const io = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                setInView(true);
                io.disconnect();
            }
        }, options);
        io.observe(el);
        return () => io.disconnect();
    }, [inView]); // eslint-disable-line react-hooks/exhaustive-deps

    return [ref, inView];
}

/**
 * Counts up to `target` once `start` is true; later changes animate from the
 * current value (e.g. 5 → 4 beds), not from 0. Instant with reduced motion.
 */
export function useCountUp(target, start, duration = 700) {
    const [value, setValue] = useState(prefersReducedMotion() ? target : 0);
    const fromRef = useRef(prefersReducedMotion() ? target : 0);

    useEffect(() => {
        if (!start) return;
        if (prefersReducedMotion()) {
            setValue(target);
            fromRef.current = target;
            return;
        }
        let raf;
        const from = fromRef.current;
        const t0 = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - t0) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            const v = Math.round(from + (target - from) * eased);
            fromRef.current = v;
            setValue(v);
            if (p < 1) raf = requestAnimationFrame(tick);
        };
        raf = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(raf);
    }, [target, start, duration]);

    return value;
}

/** Lightweight one-shot confetti burst on a temporary canvas. */
export function confettiBurst({ particles = 90, duration = 1800 } = {}) {
    if (prefersReducedMotion()) return;

    const canvas = document.createElement("canvas");
    canvas.setAttribute("aria-hidden", "true");
    Object.assign(canvas.style, {
        position: "fixed", inset: "0", width: "100%", height: "100%",
        pointerEvents: "none", zIndex: "3000",
    });
    document.body.appendChild(canvas);

    const dpr = window.devicePixelRatio || 1;
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;
    const ctx = canvas.getContext("2d");
    ctx.scale(dpr, dpr);

    const colors = ["#e06800", "#f97316", "#00a86b", "#16a34a", "#fbbf24", "#0d2347"];
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight * 0.35;
    const bits = Array.from({ length: particles }, () => {
        const angle = Math.random() * Math.PI * 2;
        const speed = 4 + Math.random() * 7;
        return {
            x: cx, y: cy,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed - 4,
            size: 5 + Math.random() * 5,
            rot: Math.random() * Math.PI,
            vr: (Math.random() - 0.5) * 0.3,
            color: colors[(Math.random() * colors.length) | 0],
        };
    });

    const t0 = performance.now();
    const frame = (now) => {
        const elapsed = now - t0;
        ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
        ctx.globalAlpha = Math.max(0, 1 - elapsed / duration);
        for (const b of bits) {
            b.vy += 0.25;
            b.vx *= 0.99;
            b.x += b.vx;
            b.y += b.vy;
            b.rot += b.vr;
            ctx.save();
            ctx.translate(b.x, b.y);
            ctx.rotate(b.rot);
            ctx.fillStyle = b.color;
            ctx.fillRect(-b.size / 2, -b.size / 4, b.size, b.size / 2);
            ctx.restore();
        }
        if (elapsed < duration) requestAnimationFrame(frame);
        else canvas.remove();
    };
    requestAnimationFrame(frame);
}

/**
 * Blade-side motion for server-rendered pages:
 *  [data-reveal]       fade-up when scrolled into view
 *  [data-count-to="N"] count up the number (keeps data-count-prefix)
 */
export function initPageMotion(root = document) {
    const reduced = prefersReducedMotion();

    root.querySelectorAll("[data-reveal]:not([data-reveal-bound])").forEach((el) => {
        el.dataset.revealBound = "1";
        el.classList.add("rb-reveal");
        if (reduced || !("IntersectionObserver" in window)) {
            el.classList.add("is-visible");
            return;
        }
        const io = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                el.classList.add("is-visible");
                io.disconnect();
            }
        }, { threshold: 0.15 });
        io.observe(el);
    });

    root.querySelectorAll("[data-count-to]:not([data-count-bound])").forEach((el) => {
        el.dataset.countBound = "1";
        const target = parseFloat(el.dataset.countTo) || 0;
        const prefix = el.dataset.countPrefix || "";
        const fmt = (n) => prefix + Math.round(n).toLocaleString("en-PH");
        if (reduced || target <= 0) {
            el.textContent = fmt(target);
            return;
        }
        el.textContent = fmt(0);
        const t0 = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - t0) / 800);
            el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
}
