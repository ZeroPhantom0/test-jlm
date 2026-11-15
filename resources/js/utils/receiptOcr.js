// resources/js/utils/receiptOcr.js
//
// Shared receipt scanner for every tenant payment screen (Pay Now,
// Reservations, Room Change, Stay Extension). Compresses the photo in the
// browser first — OCR.space rejects files over 1 MB, and phone photos are
// usually 2–10 MB — then asks the server to read the reference number.
// The ORIGINAL file is still what gets uploaded as payment proof.

const ENDPOINT = "/tenants/dashboard/parse-receipt";
const MAX_EDGE = 2000;
const MAX_BYTES = 950 * 1024;

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content || window.__appConfig?.csrf || "";

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error("decode")); };
        img.src = url;
    });
}

const toBlob = (canvas, quality) => new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", quality));

/** Downscale/re-encode to a JPEG under ~950 KB. Falls back to the original file. */
export async function compressForOcr(file) {
    if (!file || file.size <= MAX_BYTES) return file;
    try {
        const img = await loadImage(file);
        let edge = MAX_EDGE;
        for (let attempt = 0; attempt < 4; attempt++) {
            const scale = Math.min(1, edge / Math.max(img.naturalWidth, img.naturalHeight));
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(img.naturalWidth * scale);
            canvas.height = Math.round(img.naturalHeight * scale);
            const ctx = canvas.getContext("2d");
            ctx.fillStyle = "#fff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const blob = await toBlob(canvas, 0.85);
            if (blob && blob.size <= MAX_BYTES) return new File([blob], "receipt.jpg", { type: "image/jpeg" });
            edge = Math.round(edge * 0.75);
        }
    } catch {
        // e.g. HEIC the browser can't decode — let the server try
    }
    return file;
}

/**
 * @returns {Promise<{ reference: string|null, error: string }>}
 *   reference — the detected reference number (or null)
 *   error     — a friendly message to show when nothing was detected
 */
export async function scanReceipt(file) {
    if (!file) return { reference: null, error: "" };
    try {
        const fd = new FormData();
        fd.append("receipt", await compressForOcr(file));

        const res = await fetch(ENDPOINT, {
            method: "POST",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf(), "X-Requested-With": "XMLHttpRequest" },
            credentials: "same-origin",
            body: fd,
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 422) {
            return { reference: null, error: data.errors?.receipt?.[0] || "Please upload a JPG, PNG or WEBP image (max 10 MB)." };
        }
        if (res.ok && data.success && data.reference_no) return { reference: data.reference_no, error: "" };
        if (res.ok && data.success) return { reference: null, error: "We couldn't find a reference number on this receipt. Please type it in." };
        return { reference: null, error: data.error || "We couldn't scan this receipt. Please type the reference number." };
    } catch {
        return { reference: null, error: "Receipt scanning is unavailable right now. Please type the reference number." };
    }
}
