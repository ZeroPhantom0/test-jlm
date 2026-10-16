// resources/js/app.jsx
import '../css/app.css';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

// Tenant components
import Sidebar from './components/Sidebar';
import TopHeader from './components/TopHeader';

// Admin components
import AdminSidebar from './components/AdminSidebar';
import AdminTopHeader from './components/AdminTopHeader';

// Admin modal components
import RoomsModals from './pages/Admin/RoomsModals';
import TenantsModals from './pages/Admin/TenantsModals';
import ServicesModals from './pages/Admin/ServicesModals';
import BillingModals from './pages/Admin/BillingModals';
import ReservationsModals from './pages/Admin/ReservationsModals';
import AnnouncementsModals from './pages/Admin/AnnouncementsModals';
import VirtualTourModals from './pages/Admin/VirtualTourModals';
import SettingsArchive from './pages/Admin/SettingsArchive';
import RoomChangeModals from './pages/Admin/RoomChangeModals';
import StayExtensionModals from './pages/Admin/StayExtensionModals';

// Tenant service modals
import TenantMaintenanceModals   from './pages/Services/TenantMaintenanceModals';
import TenantRoomChangeModals    from './pages/Services/RoomChangeModals';
import TenantStayExtensionModals from './pages/Services/StayExtensionModals';

// Tenant billing modal
import PaymentModal from './pages/Billing/PaymentModal';

// Tenant reservations modal
import TenantReservationsModal from './pages/Reservations/ReservationsModal';

// Tenant announcements modal
import TenantAnnouncementsModals from './pages/Announcements/AnnouncementsModals';

// Tenant history modal
import TenantHistoryModals from './pages/History/HistoryModals';

// ✅ Tenant settings accordion (behavior only, not a modal)
import TenantSettingsAccordions from './pages/Settings/SettingsAccordions';

// ============================================================
// INERTIA APP BOOTSTRAP
// ============================================================
const inertiaRoot     = document.getElementById('app');
const inertiaPageData = document.querySelector('script[data-page="app"]');

if (inertiaRoot && inertiaPageData) {
    createInertiaApp({
        resolve: (name) => {
            const pages = import.meta.glob('./pages/**/*.jsx', { eager: true });
            return pages[`./pages/${name}.jsx`];
        },
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
        const props = JSON.parse(tenantSidebar.dataset.props || '{}');
        createRoot(tenantSidebar).render(<Sidebar {...props} />);
        tenantSidebar.dataset.mounted = 'true';
    }

    const tenantHeader = document.querySelector('[data-react="top-header"]');
    if (tenantHeader && !tenantHeader.dataset.mounted) {
        const props = JSON.parse(tenantHeader.dataset.props || '{}');
        createRoot(tenantHeader).render(<TopHeader {...props} />);
        tenantHeader.dataset.mounted = 'true';
    }

    const adminSidebar = document.querySelector('[data-react="admin-sidebar"]');
    if (adminSidebar && !adminSidebar.dataset.mounted) {
        const props = JSON.parse(adminSidebar.dataset.props || '{}');
        createRoot(adminSidebar).render(<AdminSidebar {...props} />);
        adminSidebar.dataset.mounted = 'true';
    }

    const adminHeader = document.querySelector('[data-react="admin-top-header"]');
    if (adminHeader && !adminHeader.dataset.mounted) {
        const props = JSON.parse(adminHeader.dataset.props || '{}');
        createRoot(adminHeader).render(<AdminTopHeader {...props} />);
        adminHeader.dataset.mounted = 'true';
    }

    const mountIfExists = (id, Component) => {
        const el = document.getElementById(id);
        if (el && !el.dataset.mounted) {
            createRoot(el).render(<Component />);
            el.dataset.mounted = 'true';
        }
    };

    // ---------- ADMIN MODALS ----------
    mountIfExists('admin-rooms-modals',           RoomsModals);
    mountIfExists('admin-tenants-modals',         TenantsModals);
    mountIfExists('admin-services-modals',        ServicesModals);
    mountIfExists('admin-billing-modals',         BillingModals);
    mountIfExists('admin-reservations-modals',    ReservationsModals);
    mountIfExists('admin-announcements-modals',   AnnouncementsModals);
    mountIfExists('admin-virtual-tour-modals',    VirtualTourModals);
    mountIfExists('admin-settings-archive',       SettingsArchive);
    mountIfExists('admin-room-change-modals',     RoomChangeModals);
    mountIfExists('admin-stay-extension-modals',  StayExtensionModals);

    // ---------- TENANT SERVICE MODALS ----------
    mountIfExists('tenant-maintenance-modals',    TenantMaintenanceModals);
    mountIfExists('services-room-change-root',    TenantRoomChangeModals);
    mountIfExists('services-stay-extension-root', TenantStayExtensionModals);

    // ---------- TENANT BILLING MODAL ----------
    mountIfExists('payment-modal-root',           PaymentModal);

    // ---------- TENANT RESERVATIONS MODAL ----------
    mountIfExists('tenant-reservations-modals',   TenantReservationsModal);

    // ---------- TENANT ANNOUNCEMENTS MODAL ----------
    mountIfExists('tenant-announcements-modals',  TenantAnnouncementsModals);

    // ---------- TENANT HISTORY MODAL ----------
    mountIfExists('tenant-history-modals',        TenantHistoryModals);

    // ---------- TENANT SETTINGS ACCORDIONS ---------- 
    mountIfExists('tenant-settings-accordions',   TenantSettingsAccordions);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountReact);
} else {
    mountReact();
}