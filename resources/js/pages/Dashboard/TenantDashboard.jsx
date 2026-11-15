// resources/js/pages/Dashboard/TenantDashboard.jsx
//
// Tenant dashboard — an adaptive bento layout:
//   explorer (no room)        → focus on booking
//   pending  (awaiting review) → reassurance + progress
//   resident (has a room)      → daily life + community
// Pay Now, Reserve and the 360° tour reuse the existing modals / viewer.
import React, { useCallback, useEffect, useState } from "react";
import { confettiBurst } from "../../utils/motion";
import useDashboardLive from "./hooks/useDashboardLive";
import useRoomCatalog from "./hooks/useRoomCatalog";
import ActivityTimeline from "./components/ActivityTimeline";
import AnnouncementList from "./components/AnnouncementList";
import BillTile from "./components/BillTile";
import BookingJourney, { HowItWorks } from "./components/BookingJourney";
import HeroTile from "./components/HeroTile";
import MyRoomTile from "./components/MyRoomTile";
import ProfileCompletion from "./components/ProfileCompletion";
import RecommendedRooms from "./components/RecommendedRooms";
import RoomBrowser from "./components/RoomBrowser";
import RoomDetailDrawer from "./components/RoomDetailDrawer";
import SuccessModal from "./components/SuccessModal";
import ToastHost, { toast } from "./components/Toast";
import { BentoGrid, BentoTile, btn, cx } from "./components/ui";

export default function TenantDashboard(initial) {
    const [success, setSuccess] = useState(null);
    const [detail, setDetail] = useState(null);
    const [browserOpen, setBrowserOpen] = useState(initial.tenantState !== "resident");

    const catalog = useRoomCatalog(initial.rooms);
    const { d } = useDashboardLive(initial, {
        onApproved: (next) => setSuccess({ variant: "approved", room: next.currentRoom?.num }),
        onRoomsChanged: (next) => {
            if (next.tenantState !== "resident") {
                setBrowserOpen(true);
                catalog.ensureLoaded();
            }
        },
    });

    // ── Page-level state (top of the component, as the layout depends on it) ──
    const tenantState = d.tenantState;
    const hasUnpaidBills = d.hasUnpaidBills;
    const hasRoom = d.hasRoom;
    const activeReservation = d.activeReservation;
    const mode = hasRoom ? "change" : "reserve";

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

    // First time the profile hits 100% (across visits): one confetti burst
    useEffect(() => {
        const key = `dash:profile-pct:${window.__appConfig?.userId ?? "me"}`;
        const last = Number(localStorage.getItem(key) ?? -1);
        const now = d.profile?.percent ?? 0;
        if (last >= 0 && last < 100 && now >= 100) {
            confettiBurst();
            toast("Your profile is complete!", { icon: "fa-solid fa-user-check text-emerald-600", tone: "success" });
        }
        localStorage.setItem(key, String(now));
    }, [d.profile?.percent]);

    const openBrowser = useCallback(() => {
        setBrowserOpen(true);
        catalog.ensureLoaded();
    }, [catalog.ensureLoaded]);

    const closeDetail = useCallback(() => setDetail(null), []);

    const shared = {
        announcements: (p) => <AnnouncementList items={d.announcements} href={d.links.announcements} {...p} />,
        history: (p) => <ActivityTimeline items={d.activity} href={d.links.history} {...p} />,
    };

    // Index drives the staggered fade-up (60ms apart)
    let i = 0;
    const n = () => i++;

    // ── Side column (pending state only): bill + profile next to the journey ──
    const showProfile = (d.profile?.percent ?? 100) < 100;
    const hasSide = tenantState === "pending" && (!!d.bills || showProfile);

    const side = hasSide && (
        <div className="xl:col-span-4 md:col-span-6 grid gap-4 md:grid-cols-2 xl:grid-cols-1 content-start">
            <BillTile bills={d.bills} links={d.links} xl={null} md={null} index={n()} />
            {showProfile && <ProfileCompletion profile={d.profile} xl={null} md={null} index={n()} />}
        </div>
    );

    // ── Bottom row: split 12 columns evenly (2 → 6+6, 3 → 4×3, 4 → 3×4) ──
    const bottom = [
        (p) => shared.announcements(p),
        (p) => shared.history(p),
        tenantState === "explorer" && ((p) => <HowItWorks {...p} />),
        tenantState === "explorer" && showProfile && ((p) => <ProfileCompletion profile={d.profile} {...p} />),
    ].filter(Boolean);
    const bottomXl = { 2: 6, 3: 4, 4: 3 }[bottom.length] || 6;
    // Tablet (6 cols): two per row; an odd last tile goes full width
    const bottomMd = (k) => (bottom.length % 2 === 1 && k === bottom.length - 1 ? 6 : 3);

    return (
        <div className="space-y-4">
            <BentoGrid>
                <HeroTile d={d} index={n()} />

                {tenantState === "explorer" && (
                    <>
                        {/* Bill (if any) as a slim strip, then picks across the full width */}
                        <BillTile bills={d.bills} links={d.links} layout="strip" xl={12} md={6} index={n()} />
                        <RecommendedRooms catalog={catalog} mode={mode} onDetails={setDetail} onBrowse={openBrowser}
                            xl={12} md={6} index={n()} />
                    </>
                )}

                {tenantState === "pending" && (
                    <>
                        <BookingJourney reservation={activeReservation} profile={d.profile} links={d.links}
                            xl={hasSide ? 8 : 12} md={6} index={n()} />
                        {side}
                        <RecommendedRooms catalog={catalog} mode={mode} onDetails={setDetail} onBrowse={openBrowser} compact xl={12} md={6} index={n()} />
                    </>
                )}

                {tenantState === "resident" && (
                    <>
                        {/* Slim full-width bill strip, then the room */}
                        <BillTile bills={d.bills} links={d.links} layout="strip" xl={12} md={6} index={n()} />
                        <MyRoomTile room={d.currentRoom} links={d.links} access={d.access} xl={12} md={6} index={n()} />
                    </>
                )}

                {bottom.map((render, k) => <React.Fragment key={k}>{render({ xl: bottomXl, md: bottomMd(k), index: n() })}</React.Fragment>)}
            </BentoGrid>

            {/* Browse all rooms — always on for explorer/pending; on demand for residents */}
            {browserOpen ? (
                <BentoTile tone="none" className="pt-2">
                    <RoomBrowser catalog={catalog} mode={mode} onDetails={setDetail} defaultTab={tenantState === "explorer" ? "all" : "foryou"} />
                </BentoTile>
            ) : (
                <BentoTile className="p-5 flex flex-col sm:flex-row sm:items-center gap-4" index={n()}>
                    <span className="w-10 h-10 rounded-xl bg-orange-50 text-[#BF5600] flex items-center justify-center text-[16px] shrink-0" aria-hidden="true">
                        <i className="fa-solid fa-compass" />
                    </span>
                    <div className="flex-1 min-w-0">
                        <h2 className="text-[15px] font-extrabold text-slate-900">Explore other rooms</h2>
                        <p className="text-[12.5px] text-slate-500">
                            {d.access.canRoomChange ? "Thinking about a move? See what's open and request a room change." : "Have a look at what's open right now."}
                        </p>
                    </div>
                    <button type="button" onClick={openBrowser} className={cx(btn.secondary, btn.md)}>
                        Browse rooms <i className="fa-solid fa-chevron-down text-[11px]" aria-hidden="true" />
                    </button>
                </BentoTile>
            )}

            {detail && (
                <RoomDetailDrawer room={detail} mode={mode} roomChange={catalog.data?.roomChange} houseRulesUrl={d.links.houseRules} onClose={closeDetail} />
            )}
            {success && <SuccessModal variant={success.variant} room={success.room} onClose={() => setSuccess(null)} />}
            <ToastHost />
        </div>
    );
}
