// resources/js/app.jsx
import "../css/app.css";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";

// ── WebSocket / Real-time Imports ──
import Echo from "laravel-echo";
import Pusher from "pusher-js";

// Tenant components
import Sidebar from "./components/Sidebar";
import TopHeader from "./components/TopHeader";

// Admin components
import AdminSidebar from "./components/AdminSidebar";
import AdminTopHeader from "./components/AdminTopHeader";

// Global Modals
import LogoutModal from "./components/LogoutModal";

// Admin modal components
import RoomsModals from "./pages/Admin/RoomsModals";
import TenantsModals from "./pages/Admin/TenantsModals";
import ServicesModals from "./pages/Admin/ServicesModals";
import BillingModals from "./pages/Admin/BillingModals";
import ReservationsModals from "./pages/Admin/ReservationsModals";
import AnnouncementsModals from "./pages/Admin/AnnouncementsModals";
import VirtualTourModals from "./pages/Admin/VirtualTourModals";
import SettingsArchive from "./pages/Admin/SettingsArchive";
import RoomChangeModals from "./pages/Admin/RoomChangeModals";
import StayExtensionModals from "./pages/Admin/StayExtensionModals";
import ScenesModals from "./pages/Admin/ScenesModals";
import MessagesModals from "./pages/Admin/MessagesModals";

// Tenant service modals
import TenantMaintenanceModals from "./pages/Services/TenantMaintenanceModals";
import TenantRoomChangeModals from "./pages/Services/RoomChangeModals";
import TenantStayExtensionModals from "./pages/Services/StayExtensionModals";

// Tenant billing modal
import PaymentModal from "./pages/Billing/PaymentModal";

// Tenant reservations modal
import TenantReservationsModal from "./pages/Reservations/ReservationsModal";

// Tenant announcements modal
import TenantAnnouncementsModals from "./pages/Announcements/AnnouncementsModals";

// Tenant history modal
import TenantHistoryModals from "./pages/History/HistoryModals";

// Tenant settings accordion
import TenantSettingsAccordions from "./pages/Settings/SettingsAccordions";

// ============================================================
// ECHO (REAL-TIME) BOOTSTRAP — SINGLE SOURCE OF TRUTH
// ============================================================
//
// IMPORTANT: This is the ONLY place where Echo is created.
// The Blade layouts (admin.blade.php, tenant.blade.php) no longer
// load Pusher/Echo from CDN or call `new Echo(...)` — they only
// expose `window.__appConfig.broadcast` so this file can read it.
//
// Guard with `if (!window.Echo)` so Vite HMR reloads don't leak
// WebSocket connections. Disconnect on HMR dispose (see bottom).

(function bootstrapEcho() {
    // Already connected — never create a second socket.
    if (window.Echo) return;

    window.Pusher = Pusher;

    const cfg = window.__appConfig?.broadcast || {};
    const key     = cfg.key     || import.meta.env.VITE_PUSHER_APP_KEY;
    const cluster = cfg.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER || "ap1";

    if (!key) {
        console.warn("[Echo] Pusher key missing — real-time disabled, polling fallback active.");
        window.__liveAvailable = false;
        return;
    }

    try {
        window.Echo = new Echo({
            broadcaster: "pusher",
            key,
            cluster,
            forceTLS: true,
            authEndpoint: "/broadcasting/auth",
            auth: {
                headers: {
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || window.__appConfig?.csrf,
                    Accept: "application/json",
                },
            },
        });

        // ── Connection state (used by polling fallbacks everywhere) ──
        window.__liveAvailable = false;

        const conn = window.Echo.connector.pusher.connection;

        conn.bind("connected", () => {
            window.__liveAvailable = true;
            document.dispatchEvent(new CustomEvent("echo:connected"));
        });

        conn.bind("unavailable", () => {
            window.__liveAvailable = false;
            document.dispatchEvent(new CustomEvent("echo:unavailable"));
        });

        conn.bind("error", (err) => {
            window.__liveAvailable = false;
            console.warn("[Echo] connection error:", err);
            document.dispatchEvent(new CustomEvent("echo:unavailable"));
        });

        // ── Shared channel helpers (importable by any page) ──
        window.LiveAdminChannels = {
            reservations: (handler) => {
                const ch = window.Echo.channel("admin.reservations");
                ch.listen(".reservation.updated", handler);
                return () => ch.stopListening(".reservation.updated", handler);
            },
            billing: (handler) => {
                const ch = window.Echo.channel("admin.billing");
                ch.listen(".invoice.updated", handler);
                return () => ch.stopListening(".invoice.updated", handler);
            },
            announcements: (handler) => {
                const ch = window.Echo.channel("admin.announcements");
                ch.listen(".announcement.updated", handler);
                return () => ch.stopListening(".announcement.updated", handler);
            },
            services: (handler) => {
                // Unified listener for all 3 service events
                const ch = window.Echo.channel("admin.services");
                const wrapped = (e) => handler(e);
                ch.listen(".maintenance.updated", wrapped);
                ch.listen(".room-change.updated", wrapped);
                ch.listen(".stay-extension.updated", wrapped);
                return () => {
                    ch.stopListening(".maintenance.updated", wrapped);
                    ch.stopListening(".room-change.updated", wrapped);
                    ch.stopListening(".stay-extension.updated", wrapped);
                };
            },
        };

        window.LiveTenantChannels = {
            reservations: (handler) => {
                const ch = window.Echo.channel("tenant.reservations");
                ch.listen(".reservation.updated", handler);
                return () => ch.stopListening(".reservation.updated", handler);
            },
            billing: (handler) => {
                const ch = window.Echo.channel("tenant.billing");
                ch.listen(".invoice.updated", handler);
                return () => ch.stopListening(".invoice.updated", handler);
            },
            announcements: (handler) => {
                const ch = window.Echo.channel("tenant.announcements");
                ch.listen(".announcement.updated", handler);
                return () => ch.stopListening(".announcement.updated", handler);
            },
            services: (handler) => {
                const ch = window.Echo.channel("tenant.services");
                const wrapped = (e) => handler(e);
                ch.listen(".maintenance.updated", wrapped);
                ch.listen(".room-change.updated", wrapped);
                ch.listen(".stay-extension.updated", wrapped);
                return () => {
                    ch.stopListening(".maintenance.updated", wrapped);
                    ch.stopListening(".room-change.updated", wrapped);
                    ch.stopListening(".stay-extension.updated", wrapped);
                };
            },
        };

        console.info("[Echo] Initialized (single instance).");
    } catch (e) {
        console.warn("[Echo] Initialization failed:", e);
        window.__liveAvailable = false;
    }
})();

// ── Vite HMR: close the socket before the module is replaced ──
if (import.meta.hot) {
    import.meta.hot.dispose(() => {
        try {
            window.Echo?.disconnect();
        } catch {}
        window.Echo = null;
        window.__liveAvailable = false;
    });
}

// ============================================================
// INERTIA APP BOOTSTRAP
// ============================================================
const inertiaRoot = document.getElementById("app");
const inertiaPageData = document.querySelector('script[data-page="app"]');

if (inertiaRoot && inertiaPageData) {
    const pages = import.meta.glob("./pages/**/*.jsx", { eager: true });

    const resolvePage = (name) => {
        const clean = name.replace(/^\.?\//, "").replace(/\.jsx$/, "");
        const candidates = [
            `./pages/${clean}.jsx`,
            `./pages/${clean}`,
            `./pages/${clean}/index.jsx`,
        ];
        for (const key of candidates) {
            if (pages[key]) return pages[key];
        }
        const lower = clean.toLowerCase();
        const foundKey = Object.keys(pages).find(
            (k) => k.toLowerCase() === `./pages/${lower}.jsx`,
        );
        if (foundKey) return pages[foundKey];

        console.error(
            `[Inertia] Page component not found for "${name}".\n` +
                `Tried: ${candidates.join(", ")}\n` +
                `Available pages:\n${Object.keys(pages).join("\n")}`,
        );
        return null;
    };

    createInertiaApp({
        resolve: resolvePage,
        setup({ el, App, props }) {
            createRoot(el).render(<App {...props} />);
        },
    });
}

// ============================================================
// EXISTING REACT COMPONENT MOUNTER (Tenant & Admin portals)
// ============================================================
function mountReact() {
    const tenantSidebar = document.querySelector('[data-react="sidebar"]');
    if (tenantSidebar && !tenantSidebar.dataset.mounted) {
        const props = JSON.parse(tenantSidebar.dataset.props || "{}");
        createRoot(tenantSidebar).render(<Sidebar {...props} />);
        tenantSidebar.dataset.mounted = "true";
    }

    const tenantHeader = document.querySelector('[data-react="top-header"]');
    if (tenantHeader && !tenantHeader.dataset.mounted) {
        const props = JSON.parse(tenantHeader.dataset.props || "{}");
        createRoot(tenantHeader).render(<TopHeader {...props} />);
        tenantHeader.dataset.mounted = "true";
    }

    const adminSidebar = document.querySelector('[data-react="admin-sidebar"]');
    if (adminSidebar && !adminSidebar.dataset.mounted) {
        const props = JSON.parse(adminSidebar.dataset.props || "{}");
        createRoot(adminSidebar).render(<AdminSidebar {...props} />);
        adminSidebar.dataset.mounted = "true";
    }

    const adminHeader = document.querySelector('[data-react="admin-top-header"]');
    if (adminHeader && !adminHeader.dataset.mounted) {
        const props = JSON.parse(adminHeader.dataset.props || "{}");
        createRoot(adminHeader).render(<AdminTopHeader {...props} />);
        adminHeader.dataset.mounted = "true";
    }

    const mountIfExists = (id, Component) => {
        const el = document.getElementById(id);
        if (el && !el.dataset.mounted) {
            createRoot(el).render(<Component />);
            el.dataset.mounted = "true";
        }
    };

    // ---------- ADMIN MODALS ----------
    mountIfExists("admin-rooms-modals", RoomsModals);
    mountIfExists("admin-tenants-modals", TenantsModals);
    mountIfExists("admin-services-modals", ServicesModals);
    mountIfExists("admin-billing-modals", BillingModals);
    mountIfExists("admin-reservations-modals", ReservationsModals);
    mountIfExists("admin-announcements-modals", AnnouncementsModals);
    mountIfExists("admin-virtual-tour-modals", VirtualTourModals);
    mountIfExists("admin-settings-archive", SettingsArchive);
    mountIfExists("admin-room-change-modals", RoomChangeModals);
    mountIfExists("admin-stay-extension-modals", StayExtensionModals);
    mountIfExists("admin-scenes-modals", ScenesModals);
    mountIfExists("admin-messages-root", MessagesModals);

    // ---------- TENANT SERVICE MODALS ----------
    mountIfExists("tenant-maintenance-modals", TenantMaintenanceModals);
    mountIfExists("services-room-change-root", TenantRoomChangeModals);
    mountIfExists("services-stay-extension-root", TenantStayExtensionModals);

    // ---------- TENANT BILLING MODAL ----------
    mountIfExists("payment-modal-root", PaymentModal);

    // ---------- TENANT RESERVATIONS MODAL ----------
    mountIfExists("tenant-reservations-modals", TenantReservationsModal);

    // ---------- TENANT ANNOUNCEMENTS MODAL ----------
    mountIfExists("tenant-announcements-modals", TenantAnnouncementsModals);

    // ---------- TENANT HISTORY MODAL ----------
    mountIfExists("tenant-history-modals", TenantHistoryModals);

    // ---------- TENANT SETTINGS ACCORDIONS ----------
    mountIfExists("tenant-settings-accordions", TenantSettingsAccordions);

    // ---------- GLOBAL LOGOUT MODAL ----------
    let logoutModalRoot = document.getElementById("global-logout-modal-root");
    if (!logoutModalRoot) {
        logoutModalRoot = document.createElement("div");
        logoutModalRoot.id = "global-logout-modal-root";
        document.body.appendChild(logoutModalRoot);
    }
    if (!logoutModalRoot.dataset.mounted) {
        createRoot(logoutModalRoot).render(<LogoutModal />);
        logoutModalRoot.dataset.mounted = "true";
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountReact);
} else {
    mountReact();
}