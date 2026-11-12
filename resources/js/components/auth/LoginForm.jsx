// resources/js/components/auth/LoginForm.jsx
//
// Shared inline validation + field UI for the tenant and admin login pages.
//
// Behaviour
//  - A field is checked when it loses focus, then re-checked as you type,
//    so errors never flash while someone is still typing for the first time.
//  - Submit checks every field and moves focus to the first invalid one.
//  - Server errors (wrong credentials, wrong portal, lockout) clear as soon
//    as the related field is edited.
//  - Inputs carry aria-invalid / aria-describedby for screen readers.

import React, { useRef, useState } from "react";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export function validateLoginField(name, value) {
    const v = String(value ?? "");

    if (name === "email") {
        const email = v.trim();
        if (!email) return "Email address is required.";
        if (email.length > 255) return "Email address must not exceed 255 characters.";
        if (!EMAIL_RE.test(email)) return "Enter a valid email address, e.g. name@example.com.";
    }

    if (name === "password") {
        if (!v) return "Password is required.";
        if (v.length > 255) return "Password must not exceed 255 characters.";
    }

    return null;
}

/**
 * @param {object} form  Inertia useForm() result ({ data, setData, errors, clearErrors })
 * @param {string[]} fields  field names in on-screen order
 */
export function useLoginValidation(form, fields = ["email", "password"]) {
    const [touched, setTouched] = useState({});
    const [clientErrors, setClientErrors] = useState({});
    const refs = useRef({});

    const register = (name) => (el) => {
        refs.current[name] = el;
    };

    const check = (name, value) => {
        const err = validateLoginField(name, value);
        setClientErrors((prev) => ({ ...prev, [name]: err }));
        return err;
    };

    const onChange = (name, value) => {
        form.setData(name, value);
        // Editing a field clears the server message tied to it, plus the
        // generic "incorrect email or password" banner
        if (form.errors[name] || form.errors.credentials) form.clearErrors(name, "credentials");
        if (touched[name]) check(name, value);
    };

    const onBlur = (name) => {
        setTouched((prev) => ({ ...prev, [name]: true }));
        check(name, form.data[name]);
    };

    /** Validate everything; focus the first invalid field. Returns true if OK. */
    const validateAll = () => {
        const next = {};
        fields.forEach((f) => (next[f] = validateLoginField(f, form.data[f])));
        setClientErrors(next);
        setTouched(Object.fromEntries(fields.map((f) => [f, true])));

        const firstBad = fields.find((f) => next[f]);
        if (firstBad) {
            refs.current[firstBad]?.focus();
            return false;
        }
        return true;
    };

    const errorFor = (name) => clientErrors[name] || form.errors[name] || null;

    return {
        register,
        onChange,
        onBlur,
        validateAll,
        errorFor,
        bannerError: form.errors.credentials || null,
    };
}

export function LoginField({
    name,
    label,
    type = "text",
    icon,
    placeholder,
    autoComplete,
    autoFocus,
    value,
    validation,
    revealable = false,
}) {
    const [show, setShow] = useState(false);
    const error = validation.errorFor(name);
    const errorId = `${name}-error`;
    const inputType = revealable ? (show ? "text" : "password") : type;

    return (
        <div className="login-form-group">
            <label htmlFor={`login-${name}`}>{label}</label>
            <div className={`login-input-wrapper ${error ? "is-invalid" : ""}`}>
                <span className="login-input-icon">
                    <i className={icon}></i>
                </span>
                <input
                    id={`login-${name}`}
                    ref={validation.register(name)}
                    name={name}
                    type={inputType}
                    placeholder={placeholder}
                    value={value}
                    onChange={(e) => validation.onChange(name, e.target.value)}
                    onBlur={() => validation.onBlur(name)}
                    autoComplete={autoComplete}
                    autoFocus={autoFocus}
                    aria-invalid={error ? "true" : "false"}
                    aria-describedby={error ? errorId : undefined}
                    maxLength={255}
                />
                {revealable && (
                    <button
                        type="button"
                        className="password-toggle"
                        onClick={() => setShow((s) => !s)}
                        aria-label={show ? "Hide password" : "Show password"}
                    >
                        <i className={show ? "bi bi-eye-slash" : "bi bi-eye"}></i>
                    </button>
                )}
            </div>
            {error && (
                <p id={errorId} className="login-field-error" role="alert">
                    <i className="bi bi-exclamation-circle-fill"></i>
                    <span>{error}</span>
                </p>
            )}
        </div>
    );
}

/** Banner for errors that belong to the whole form (credentials, lockout, flash). */
export function LoginAlert({ message }) {
    if (!message) return null;
    return (
        <div className="login-global-error" role="alert">
            <i className="bi bi-exclamation-triangle-fill"></i>
            <span>{message}</span>
        </div>
    );
}
