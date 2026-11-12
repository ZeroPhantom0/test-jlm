// resources/js/utils/settingsForms.js
//
// Saves small settings forms (Security Options, Preferences) without a page
// reload. Mark a form with `data-settings-save` and `data-url="<PUT url>"`.
// Checkboxes are sent as true/false, other fields by value. A
// `.settings-save-status` element inside the form shows the result.

function csrfToken() {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
        window.__appConfig?.csrf ||
        ""
    );
}

function payloadOf(form) {
    const data = {};
    form.querySelectorAll("input[name], select[name], textarea[name]").forEach((el) => {
        if (el.type === "checkbox") data[el.name] = el.checked;
        else data[el.name] = el.value;
    });
    return data;
}

function setStatus(form, text, kind) {
    const el = form.querySelector(".settings-save-status");
    if (!el) return;
    el.textContent = text;
    el.className =
        "settings-save-status text-[12px] font-medium " +
        (kind === "ok" ? "text-emerald-600" : kind === "err" ? "text-rose-600" : "text-slate-500");
}

function bind(form) {
    if (form.__settingsBound) return;
    form.__settingsBound = true;

    // Keep the "Enabled / Disabled" captions next to toggles in sync
    form.querySelectorAll('input[type="checkbox"][data-status-target]').forEach((cb) => {
        const target = document.getElementById(cb.dataset.statusTarget);
        const sync = () => target && (target.textContent = cb.checked ? "Enabled" : "Disabled");
        cb.addEventListener("change", sync);
        sync();
    });

    form.addEventListener("input", () => setStatus(form, "Unsaved changes", "muted"));

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;
        setStatus(form, "Saving…", "muted");

        try {
            const res = await fetch(form.dataset.url, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                credentials: "same-origin",
                body: JSON.stringify(payloadOf(form)),
            });
            const json = await res.json().catch(() => ({}));

            if (res.status === 401 && json.redirect) {
                window.location.href = json.redirect;
                return;
            }
            if (!res.ok) {
                const firstError = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                throw new Error(firstError || json.message || "Could not save.");
            }

            setStatus(form, json.message || "Saved.", "ok");
            if (form.id === "security-options-form" && json.session_timeout && window.__appConfig) {
                window.__appConfig.sessionTimeout = json.session_timeout;
                document.dispatchEvent(new CustomEvent("session-timeout:changed"));
            }
        } catch (err) {
            setStatus(form, err.message || "Could not save.", "err");
        } finally {
            if (btn) btn.disabled = false;
        }
    });
}

export function initSettingsForms(root = document) {
    root.querySelectorAll("form[data-settings-save]").forEach(bind);
}

export default initSettingsForms;
