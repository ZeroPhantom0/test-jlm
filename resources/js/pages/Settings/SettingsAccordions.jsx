// resources/js/pages/Settings/SettingsAccordions.jsx
import React, { useEffect } from 'react';

/**
 * Handles only the accordion toggle behavior inside the Settings page
 * (Terms & Conditions + Privacy Policy sections).
 *
 * No visible output — this component just wires up click delegation on
 * `.settings-accordion-header`.
 *
 * Contract with Blade:
 *  - Accordion item:   <div class="settings-accordion" data-accordion-key="KEY">
 *  - Accordion header: <div class="settings-accordion-header">
 *  - Accordion body:   <div class="settings-accordion-body">
 *  - Chevron:          <i class="settings-accordion-chevron">
 *
 * Accordions start COLLAPSED. Clicking a header toggles that accordion
 * independently (multiple can be open at once).
 */
export default function SettingsAccordions() {
    useEffect(() => {
        const handler = (e) => {
            const header = e.target.closest('.settings-accordion-header');
            if (!header) return;

            const item = header.closest('.settings-accordion');
            if (!item) return;

            const isOpen = item.classList.contains('expanded');
            const body   = item.querySelector('.settings-accordion-body');
            const chev   = item.querySelector('.settings-accordion-chevron');

            if (isOpen) {
                item.classList.remove('expanded');
                if (body) {
                    body.style.maxHeight = '0px';
                    body.style.padding   = '0 24px';
                    body.style.borderTop = '1px solid transparent';
                }
                if (chev) chev.style.transform = 'rotate(0deg)';
            } else {
                item.classList.add('expanded');
                if (body) {
                    body.style.maxHeight = '3000px';
                    body.style.padding   = '0 24px 24px 24px';
                    body.style.borderTop = '1px solid #f1f5f9';
                }
                if (chev) chev.style.transform = 'rotate(180deg)';
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    return null;
}