// resources/js/pages/Landing/Index.jsx
import React, { useState, useEffect, useRef, useCallback } from "react";
import { Head, Link, router } from "@inertiajs/react";
import SplashScreen from "../../components/SplashScreen";

/* ============================================================
   AMENITIES DATA
   ============================================================ */
const AMENITIES = [
    {
        id: "wifi",
        icon: "bi-wifi",
        title: "High-Speed Wi-Fi",
        description:
            "Reliable internet connection for work, communication, and entertainment.",
        image: "/images/amenities/wifi.jpg",
    },
    {
        id: "aircon",
        icon: "bi-snow",
        title: "Air-Conditioned & Fan Rooms",
        description: "Choose room options that fit your comfort and budget.",
        image: "/images/amenities/aircon.jpg",
    },
    {
        id: "netflix",
        icon: "bi-tv",
        title: "Netflix Access",
        description:
            "Enjoy entertainment and streaming access during your stay.",
        image: "/images/amenities/netflix.jpeg",
    },
    {
        id: "security",
        icon: "bi-shield-lock",
        title: "24/7 Security",
        description:
            "CCTV-monitored entrances and secure access for peace of mind.",
        image: "/images/amenities/security.PNG",
    },
    {
        id: "kitchen",
        icon: "bi-cup-hot",
        title: "Shared Kitchen",
        description:
            "A fully equipped communal kitchen for preparing your own meals.",
        image: "/images/amenities/kitchen.jpg",
    },
    {
        id: "laundry",
        icon: "bi-basket",
        title: "Laundry Area",
        description: "Designated laundry and drying spaces for tenant use.",
        image: "/images/amenities/laundry.jpg",
    },
];

/* ============================================================
   NAV LINKS
   ============================================================ */
const NAV_LINKS = [
    { id: "home", label: "Home" },
    { id: "about", label: "About" },
    { id: "services", label: "Services" },
    { id: "rooms", label: "Rooms" },
    { id: "contact", label: "Contact" },
];

/* ============================================================
   MAIN COMPONENT
   ============================================================ */
export default function Index({ rooms = [] }) {
    useEffect(() => {
        if (window.location.hash === "#rooms") {
            const el = document.getElementById("rooms");
            if (el) {
                setTimeout(
                    () =>
                        el.scrollIntoView({ behavior: "auto", block: "start" }),
                    50,
                );
            }
        }
    }, []);

    return (
        <div className="landing-page">
            <Head title="JLM Cozy Cabin" />

            <LandingHeader />
            <HeroSection />
            <AboutSection />
            <AmenitiesCarousel />
            <RoomsSection rooms={rooms} />
            <ContactSection />
            <LandingFooter />
        </div>
    );
}

/* ============================================================
   HEADER — OPTIMIZED AUTH NAVIGATION
   ============================================================ */
function LandingHeader() {
    const [activeSection, setActiveSection] = useState("home");
    const [mobileNavOpen, setMobileNavOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const [splash, setSplash] = useState(null);

    useEffect(() => {
        const sections = NAV_LINKS.map((l) =>
            document.getElementById(l.id),
        ).filter(Boolean);
        if (!sections.length) return;

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
                if (visible[0]) setActiveSection(visible[0].target.id);
            },
            {
                rootMargin: "-45% 0px -45% 0px",
                threshold: [0, 0.25, 0.5, 0.75, 1],
            },
        );

        sections.forEach((s) => observer.observe(s));
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 40);
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
        return () => window.removeEventListener("scroll", onScroll);
    }, []);

    const handleNavClick = (id) => {
        setMobileNavOpen(false);
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
    };

    // Instant navigation — triggers Inertia load WHILE the splash plays
    const handleAuthNav = (e, target) => {
        e.preventDefault();
        setMobileNavOpen(false);
        setSplash({ target });

        router.visit(target, {
            onFinish: () => {
                // Clean up splash just in case the component hasn't unmounted yet
                setTimeout(() => setSplash(null), 100);
            },
        });
    };

    return (
        <>
            {splash && (
                <SplashScreen
                    duration={800}
                    message={
                        splash.target === "/login"
                            ? "Signing you in…"
                            : "Creating your account…"
                    }
                />
            )}

            <header className={`header ${scrolled ? "header-solid" : ""}`}>
                <div className="logo">
                    <img
                        src="/images/jlm-logo.png"
                        alt="JLM Cozy Cabin Logo"
                        onError={(e) => {
                            e.currentTarget.style.display = "none";
                        }}
                    />
                    <span>JLM Cozy Cabin</span>
                </div>

                <nav
                    className={`navigation ${mobileNavOpen ? "mobile-open" : ""}`}
                >
                    {NAV_LINKS.map((link) => (
                        <a
                            key={link.id}
                            href={`#${link.id}`}
                            className={`nav-link ${activeSection === link.id ? "active" : ""}`}
                            onClick={(e) => {
                                e.preventDefault();
                                handleNavClick(link.id);
                            }}
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>

                <div className="header-buttons">
                    <a
                        href="/login"
                        className="login-btn"
                        onClick={(e) => handleAuthNav(e, "/login")}
                    >
                        Log In
                    </a>
                    <a
                        href="/signup"
                        className="signup-btn"
                        onClick={(e) => handleAuthNav(e, "/signup")}
                    >
                        Get Started
                    </a>

                    <button
                        type="button"
                        className="mobile-menu-toggle"
                        aria-label="Toggle menu"
                        onClick={() => setMobileNavOpen((v) => !v)}
                    >
                        <i
                            className={`bi ${mobileNavOpen ? "bi-x-lg" : "bi-list"}`}
                        />
                    </button>
                </div>
            </header>
        </>
    );
}

/* ============================================================
   HERO
   ============================================================ */
function HeroSection() {
    return (
        <section className="hero" id="home">
            <div className="hero-overlay" />
            <div className="hero-content">
                <h1>
                    Affordable &amp; Accessible
                    <br />
                    <span>Living in Ermita,</span> Manila
                </h1>
                <p>
                    Your clean, comfortable room awaits — anytime, 24/7. Find
                    the perfect space whether you're a student, trainee,
                    seafarer, or OFW.
                </p>
                <a href="#rooms" className="hero-btn">
                    Find a Room <i className="bi bi-arrow-right" />
                </a>
            </div>
        </section>
    );
}

/* ============================================================
   ABOUT
   ============================================================ */
function AboutSection() {
    return (
        <section className="about-section" id="about">
            <div className="about-images">
                <div className="about-image image-1" />
                <div className="about-image image-2" />
                <div className="about-image image-3" />
                <div className="about-image image-4" />
            </div>

            <div className="about-content">
                <p className="section-label">About Us</p>
                <h2>
                    More Than Just a Room — <span>A Home</span>
                </h2>
                <p className="about-description">
                    JLM Cozy Cabin provides affordable and comfortable
                    accommodations in Ermita, Manila — designed for individuals
                    and groups looking for a clean, secure, and accessible place
                    to stay. Whether for short-term or long-term stay, we offer
                    a home-like environment with convenience and comfort.
                </p>

                <div className="about-feature">
                    <div className="feature-icon">
                        <i className="bi bi-geo-alt" />
                    </div>
                    <div className="feature-text">
                        <h3>Prime Location</h3>
                        <p>
                            Located at 1517 M.H. Del Pilar St., Ermita Manila
                            corner Salas St. — near major agencies, business
                            centers, hospitals, and transport access.
                        </p>
                    </div>
                </div>

                <div className="about-feature">
                    <div className="feature-icon">
                        <i className="bi bi-shield-check" />
                    </div>
                    <div className="feature-text">
                        <h3>Safe &amp; Accessible</h3>
                        <p>
                            Close to the US Embassy, Manning Agencies, Training
                            Centers, Banks, Hospitals, Robinson Manila, and
                            Manila Bay.
                        </p>
                    </div>
                </div>

                <div className="about-feature">
                    <div className="feature-icon">
                        <i className="bi bi-clock" />
                    </div>
                    <div className="feature-text">
                        <h3>24/7 Open</h3>
                        <p>
                            No check-in time restrictions — guests can check in
                            anytime.
                        </p>
                    </div>
                </div>

                <a href="#rooms" className="stay-btn">
                    Start Your Stay <i className="bi bi-arrow-right" />
                </a>
            </div>
        </section>
    );
}

/* ============================================================
   AMENITIES CAROUSEL — True Infinite Loop via Slide Cloning
   ============================================================ */
function AmenitiesCarousel() {
    const [visibleCount, setVisibleCount] = useState(3);
    const [index, setIndex] = useState(0);
    const [animate, setAnimate] = useState(true);
    const touchStartX = useRef(0);
    const touchDeltaX = useRef(0);
    const isAnimatingRef = useRef(false);

    useEffect(() => {
        const update = () => {
            const w = window.innerWidth;
            if (w < 640) setVisibleCount(1);
            else if (w < 1024) setVisibleCount(2);
            else setVisibleCount(3);
        };
        update();
        window.addEventListener("resize", update);
        return () => window.removeEventListener("resize", update);
    }, []);

    const total = AMENITIES.length;
    const clonesCount = Math.min(visibleCount, total);
    const extended = [
        ...AMENITIES.slice(-clonesCount),
        ...AMENITIES,
        ...AMENITIES.slice(0, clonesCount),
    ];

    const trackOffset = clonesCount;
    const stepPct = 100 / visibleCount;

    const resetWithoutAnimation = useCallback(() => {
        setAnimate(false);
        requestAnimationFrame(() => {
            requestAnimationFrame(() => setAnimate(true));
        });
    }, []);

    const goNext = useCallback(() => {
        if (isAnimatingRef.current) return;
        isAnimatingRef.current = true;
        setAnimate(true);
        setIndex((i) => i + 1);
    }, []);

    const goPrev = useCallback(() => {
        if (isAnimatingRef.current) return;
        isAnimatingRef.current = true;
        setAnimate(true);
        setIndex((i) => i - 1);
    }, []);

    const handleTransitionEnd = (e) => {
        if (e.propertyName !== "transform") return;
        if (index < 0) {
            resetWithoutAnimation();
            setIndex(total - 1);
        } else if (index >= total) {
            resetWithoutAnimation();
            setIndex(0);
        }
        isAnimatingRef.current = false;
    };

    const goTo = (realIndex) => {
        if (isAnimatingRef.current) return;
        setAnimate(true);
        setIndex(realIndex);
    };

    useEffect(() => {
        const handler = (e) => {
            if (e.key === "ArrowRight") goNext();
            if (e.key === "ArrowLeft") goPrev();
        };
        window.addEventListener("keydown", handler);
        return () => window.removeEventListener("keydown", handler);
    }, [goNext, goPrev]);

    const onTouchStart = (e) => {
        touchStartX.current = e.touches[0].clientX;
        touchDeltaX.current = 0;
    };
    const onTouchMove = (e) => {
        touchDeltaX.current = e.touches[0].clientX - touchStartX.current;
    };
    const onTouchEnd = () => {
        if (touchDeltaX.current > 50) goPrev();
        if (touchDeltaX.current < -50) goNext();
        touchDeltaX.current = 0;
    };

    const offset = (index + trackOffset) * stepPct;

    const trackStyle = {
        transform: `translateX(-${offset}%)`,
        transition: animate
            ? "transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)"
            : "none",
    };

    const slideStyle = {
        flex: `0 0 ${stepPct}%`,
        maxWidth: `${stepPct}%`,
    };

    const activeDot = ((index % total) + total) % total;

    return (
        <section className="amenities-section" id="services">
            <div className="amenities-header">
                <h2>Everything You Need, All Included</h2>
                <p>
                    From high-speed Wi-Fi and Netflix access to clean,
                    comfortable rooms — everything is designed to make your stay
                    convenient and stress-free.
                </p>
            </div>

            <div className="amenities-carousel-wrapper">
                <button
                    type="button"
                    className="carousel-btn carousel-btn-prev"
                    onClick={goPrev}
                    aria-label="Previous amenities"
                >
                    <i className="bi bi-chevron-left" />
                </button>

                <div
                    className="amenities-carousel-viewport"
                    onTouchStart={onTouchStart}
                    onTouchMove={onTouchMove}
                    onTouchEnd={onTouchEnd}
                >
                    <div
                        className="amenities-track"
                        style={trackStyle}
                        onTransitionEnd={handleTransitionEnd}
                    >
                        {extended.map((a, i) => {
                            const isClone = i < clonesCount || i >= clonesCount + total;
                            return (
                                <div
                                    className="amenity-slide"
                                    style={slideStyle}
                                    key={`${a.id}-${i}`}
                                    aria-hidden={isClone ? "true" : undefined}
                                >
                                    <div className="amenity-card">
                                        <div className={`amenity-image image-${a.id}`}>
                                            <img
                                                src={a.image}
                                                alt={a.title}
                                                className="amenity-img"
                                                loading="lazy"
                                                onError={(e) => {
                                                    e.currentTarget.style.display = "none";
                                                }}
                                            />
                                            <div className="amenity-icon">
                                                <i className={`bi ${a.icon}`} />
                                            </div>
                                        </div>
                                        <div className="amenity-content">
                                            <h3>{a.title}</h3>
                                            <p>{a.description}</p>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                <button
                    type="button"
                    className="carousel-btn carousel-btn-next"
                    onClick={goNext}
                    aria-label="Next amenities"
                >
                    <i className="bi bi-chevron-right" />
                </button>
            </div>

            <div className="carousel-dots">
                {AMENITIES.map((_, i) => (
                    <button
                        key={i}
                        type="button"
                        className={`carousel-dot ${activeDot === i ? "active" : ""}`}
                        onClick={() => goTo(i)}
                        aria-label={`Go to slide ${i + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}

/* ============================================================
   ROOMS
   ============================================================ */
function RoomsSection({ rooms }) {
    const publishedRooms = rooms.filter((r) => {
        const status = String(r.status || "").toLowerCase();
        if (status === "draft") return false;
        return true;
    });

    const soloRooms = publishedRooms.filter((r) => r.room_type === "Solo Room");
    const bedspaceRooms = publishedRooms.filter((r) => r.room_type === "Bedspace");
    const barkadaRooms = publishedRooms.filter((r) => r.room_type === "Barkada Room");

    return (
        <section className="rooms-section" id="rooms">
            <div className="rooms-header">
                <h2>Find Your Perfect Room</h2>
                <p>
                    Browse all available room types and take a virtual tour
                    before you reserve.
                </p>
            </div>

            <div className="room-status-legend">
                <div className="status-item">
                    <span className="status-dot available" /> Available
                </div>
                <div className="status-item">
                    <span className="status-dot reserved" /> Reserved
                </div>
                <div className="status-item">
                    <span className="status-dot occupied" /> Occupied
                </div>
            </div>

            {soloRooms.length > 0 && (
                <>
                    <div className="category-header">
                        <h3 className="category-title">Solo Rooms</h3>
                        <div className="category-line" />
                    </div>
                    <div className="rooms-grid-3">
                        {soloRooms.map((room) => (
                            <RoomCard key={room.id} room={room} />
                        ))}
                    </div>
                </>
            )}

            {bedspaceRooms.length > 0 && (
                <>
                    <div className="category-header">
                        <h3 className="category-title">Bedspace</h3>
                        <div className="category-line" />
                    </div>
                    <div className="rooms-grid-3">
                        {bedspaceRooms.map((room) => (
                            <RoomCard key={room.id} room={room} />
                        ))}
                    </div>
                </>
            )}

            {barkadaRooms.length > 0 && (
                <>
                    <div className="category-header">
                        <h3 className="category-title">Barkada Rooms</h3>
                        <div className="category-line" />
                    </div>
                    <div className="rooms-grid-3">
                        {barkadaRooms.map((room) => (
                            <RoomCard key={room.id} room={room} />
                        ))}
                    </div>
                </>
            )}

            {publishedRooms.length === 0 && (
                <div className="rooms-empty">
                    <i className="bi bi-door-closed" />
                    <p>
                        No rooms are available right now. Please check back
                        soon.
                    </p>
                </div>
            )}
        </section>
    );
}

function RoomCard({ room }) {
    const statusClass =
        {
            Available: "available-status",
            Reserved: "reserved-status",
            Occupied: "occupied-status",
        }[room.status] || "available-status";

    const isDraft = String(room.status || "").toLowerCase() === "draft";
    const tourIsPublished =
        room.tour_status === undefined || room.tour_status === "Published";
    const hasTour =
        !isDraft && room.has_tour === true && !!room.tour_id && tourIsPublished;
    const tourUrl = hasTour ? `/tour/${room.tour_id}` : null;

    const ImageWrapper = hasTour ? "a" : "div";
    const imageProps = hasTour
        ? {
              href: tourUrl,
              className: "room-image clickable",
              "aria-label": `Open 360° tour for ${room.room_type}`,
              rel: "noopener",
          }
        : { className: "room-image" };

    return (
        <div className="room-card compact">
            <ImageWrapper {...imageProps}>
                {room.thumbnail && (
                    <img
                        src={room.thumbnail}
                        alt={room.room_type}
                        className="room-thumbnail"
                        onError={(e) => {
                            e.currentTarget.style.display = "none";
                        }}
                    />
                )}
                <div className={`room-status ${statusClass}`}>
                    <i className="bi bi-circle-fill" /> {room.status}
                </div>
                {hasTour && (
                    <div className="recommended-tag">
                        <i className="bi bi-camera-video-fill" /> Virtual Tour
                    </div>
                )}
                <div className="views">
                    <i className="bi bi-house-door" /> Room {room.room_number}
                </div>
            </ImageWrapper>

            <div className="room-info">
                <div className="room-title-row">
                    <div>
                        <h3>{room.room_type}</h3>
                        <p className="room-type">Floor {room.floor}</p>
                    </div>
                    <div className="room-price">
                        ₱{Number(room.daily_rent).toLocaleString()}
                        <span>per day</span>
                    </div>
                </div>

                <div className="room-details">
                    <span>
                        <i className="bi bi-house-door" /> {room.total_beds}{" "}
                        {room.total_beds > 1 ? "Beds" : "Bed"}
                    </span>
                </div>

                <div
                    className={`availability-box ${
                        room.status === "Available"
                            ? "available-box"
                            : room.status === "Reserved"
                              ? "reserved-box"
                              : "occupied-box"
                    }`}
                >
                    <div>
                        <small>
                            <i className="bi bi-calendar3" />{" "}
                            {room.status === "Available"
                                ? "Available Now"
                                : "Current Status"}
                        </small>
                        <strong>
                            {room.status === "Available"
                                ? "Ready for Move-in"
                                : room.status}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    );
}

/* ============================================================
   CONTACT
   ============================================================ */
function ContactSection() {
    return (
        <section className="contact-section" id="contact">
            <div className="contact-container">
                <div className="contact-info">
                    <div className="contact-badge">Contact Us</div>
                    <h2>
                        Ready to Move In?
                        <span>Let's Talk.</span>
                    </h2>
                    <p className="contact-description">
                        Have questions about room availability, pricing, or our
                        facilities? We're happy to help. Reach out through any
                        of the channels below.
                    </p>
                </div>

                <div className="contact-details-grid">
                    <div className="contact-detail">
                        <div className="contact-icon">
                            <i className="bi bi-telephone" />
                        </div>
                        <div>
                            <small>PHONE</small>
                            <p>+63 912 345 6789</p>
                        </div>
                    </div>
                    <div className="contact-detail">
                        <div className="contact-icon">
                            <i className="bi bi-envelope" />
                        </div>
                        <div>
                            <small>EMAIL</small>
                            <p>info@jlmcozycabin.com</p>
                        </div>
                    </div>
                    <div className="contact-detail">
                        <div className="contact-icon">
                            <i className="bi bi-geo-alt" />
                        </div>
                        <div>
                            <small>ADDRESS</small>
                            <p>1517 M.H. Del Pilar St., Ermita, Manila</p>
                        </div>
                    </div>
                    <div className="contact-detail">
                        <div className="contact-icon">
                            <i className="bi bi-clock" />
                        </div>
                        <div>
                            <small>OFFICE HOURS</small>
                            <p>Mon–Sun, 8:00 AM – 8:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

/* ============================================================
   FOOTER
   ============================================================ */
function LandingFooter() {
    return (
        <footer className="footer">
            <div className="footer-container">
                <div className="footer-brand">
                    <div className="footer-brand-title">
                        <div className="footer-logo">JLM</div>
                        <h2>JLM Cozy Cabin</h2>
                    </div>
                    <p>
                        Premium boarding house offering comfortable, affordable,
                        and secure living in Manila.
                    </p>
                    <div className="footer-socials">
                        <a
                            href="https://www.facebook.com/profile.php?id=100076056182354"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="Facebook"
                        >
                            <i className="bi bi-facebook" />
                        </a>
                    </div>
                </div>

                <div className="footer-column">
                    <h3>Quick Links</h3>
                    <a href="#home">› Home</a>
                    <a href="#about">› About</a>
                    <a href="#services">› Services</a>
                    <a href="#rooms">› Rooms</a>
                    <a href="#contact">› Contact</a>
                </div>

                <div className="footer-column">
                    <h3>Room Types</h3>
                    <a href="#rooms">› Solo Room</a>
                    <a href="#rooms">› Barkada Room</a>
                    <a href="#rooms">› Bedspace</a>
                </div>

                <div className="footer-column">
                    <h3>Resources</h3>
                    <a href="#">› FAQs</a>
                    <a href="#">› Privacy Policy</a>
                    <a href="#">› Terms of Service</a>
                    <a href="/login">› Tenant Portal</a>
                </div>
            </div>

            <div className="footer-bottom">
                <p>© 2026 JLM Cozy Cabin. All rights reserved.</p>
            </div>
        </footer>
    );
}