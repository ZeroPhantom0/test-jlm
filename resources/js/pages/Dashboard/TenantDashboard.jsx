// resources/js/pages/Dashboard/TenantDashboard.jsx
//
// Tenant dashboard:
//   1. Hero — "Find your perfect space to stay." with a room search bar
//   2. "Choose your kind of comfort" — Solo / Barkada / Bedspace categories
//   3. Browse rooms — the filterable room browser
// Bills, reservations and room details live on the Reservations page;
// past activity lives on the History page.
import React, { useCallback, useEffect, useState } from "react";
import { confettiBurst } from "../../utils/motion";
import useRoomCatalog from "./hooks/useRoomCatalog";
import ComfortCategories from "./components/ComfortCategories";
import HeroSearch from "./components/HeroSearch";
import RoomBrowser from "./components/RoomBrowser";
import RoomDetailDrawer from "./components/RoomDetailDrawer";
import SuccessModal from "./components/SuccessModal";
import ToastHost from "./components/Toast";

const BROWSER_ID = "browse-rooms";

export default function TenantDashboard(initial) {
    const [success, setSuccess] = useState(null);
    const [detail, setDetail] = useState(null);
    // Bumped by the hero / categories so the browser jumps to "All Rooms"
    const [showAll, setShowAll] = useState(0);

    const catalog = useRoomCatalog(initial.rooms);
    const mode = initial.hasRoom ? "change" : "reserve";

    // Live room availability (public rooms channel + reservations)
    useEffect(() => {
        let timer;
        const onEvent = () => {
            clearTimeout(timer);
            timer = setTimeout(catalog.refresh, 500);
        };
        const unsubs = [];
        try {
            if (window.Echo) {
                const ch = window.Echo.channel("public.rooms");
                ch.listen(".rooms.updated", onEvent);
                unsubs.push(() => ch.stopListening(".rooms.updated", onEvent));
            }
            if (window.LiveTenantChannels?.reservations) unsubs.push(window.LiveTenantChannels.reservations(onEvent));
        } catch {
            // real-time unavailable
        }
        window.addEventListener("reservation:refresh", onEvent);
        return () => {
            clearTimeout(timer);
            unsubs.forEach((u) => typeof u === "function" && u());
            window.removeEventListener("reservation:refresh", onEvent);
        };
    }, [catalog.refresh]);

    // Booking submitted from the shared Reservations modal → celebrate
    useEffect(() => {
        const onSubmitted = (e) => {
            catalog.markOwnBooking(e.detail?.roomId ?? null);
            setSuccess({ variant: "submitted", room: e.detail?.roomNumber });
            confettiBurst();
        };
        window.addEventListener("reservation:submitted", onSubmitted);
        return () => window.removeEventListener("reservation:submitted", onSubmitted);
    }, [catalog.markOwnBooking]);

    const scrollToBrowser = () =>
        requestAnimationFrame(() => document.getElementById(BROWSER_ID)?.scrollIntoView({ behavior: "smooth", block: "start" }));

    const explore = useCallback((patch) => {
        catalog.updateFilters(patch);
        setShowAll((n) => n + 1);
        scrollToBrowser();
    }, [catalog.updateFilters]);

    const pickCategory = useCallback((type) => explore({ type: [type] }), [explore]);
    const closeDetail = useCallback(() => setDetail(null), []);

    // Counts follow the live catalog; fall back to the server-rendered ones
    const facets = catalog.data?.facets;
    const categories = (initial.categories || []).map((c) => ({
        ...c,
        available: facets?.typeCounts?.[c.type] ?? c.available,
        listed: facets?.listedCounts?.[c.type] ?? c.listed,
    }));

    return (
        <div>
            <HeroSearch firstName={initial.user?.firstName ?? "there"} filters={catalog.filters} onExplore={explore} />

            <div className="px-4 md:px-6 xl:px-8 pb-8 max-w-[1440px] mx-auto">
                <ComfortCategories categories={categories} onPick={pickCategory} />

                <RoomBrowser id={BROWSER_ID} catalog={catalog} mode={mode} onDetails={setDetail} defaultTab="all" showAllSignal={showAll} />
            </div>

            {detail && (
                <RoomDetailDrawer room={detail} mode={mode} roomChange={catalog.data?.roomChange} houseRulesUrl={initial.links?.houseRules} onClose={closeDetail} />
            )}
            {success && <SuccessModal variant={success.variant} room={success.room} onClose={() => setSuccess(null)} />}
            <ToastHost />
        </div>
    );
}
