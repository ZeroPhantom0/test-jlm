// resources/js/app.jsx
import '../css/app.css';
import { createRoot } from 'react-dom/client';

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

function mountReact() {
    // --------------------------------------------------
    // TENANT PORTAL
    // --------------------------------------------------
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

    // --------------------------------------------------
    // ADMIN PORTAL
    // --------------------------------------------------
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

    // --------------------------------------------------
    // ADMIN MODALS (only mount if their root divs exist on the page)
    // --------------------------------------------------
    const mountIfExists = (id, Component) => {
        const el = document.getElementById(id);
        if (el && !el.dataset.mounted) {
            createRoot(el).render(<Component />);
            el.dataset.mounted = 'true';
        }
    };

    mountIfExists('admin-rooms-modals',         RoomsModals);
    mountIfExists('admin-tenants-modals',       TenantsModals);
    mountIfExists('admin-services-modals',      ServicesModals);
    mountIfExists('admin-billing-modals',       BillingModals);
    mountIfExists('admin-reservations-modals',  ReservationsModals);
    mountIfExists('admin-announcements-modals', AnnouncementsModals);
    mountIfExists('admin-virtual-tour-modals',  VirtualTourModals);
    mountIfExists('admin-settings-archive',     SettingsArchive);
}

// Initial mount
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountReact);
} else {
    mountReact();
}