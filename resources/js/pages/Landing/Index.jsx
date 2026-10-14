// resources/js/pages/Landing/Index.jsx
import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Index({ rooms = [] }) {
    // Rooms that HAVE a published tour are viewable
    const tourRooms  = rooms.filter(r => r.has_tour);
    const otherRooms = rooms.filter(r => !r.has_tour);

    return (
        <div className="landing-page">
            <Head title="JLM Cozy Cabin" />

            {/* ================= HEADER ================= */}
            <header className="header">
                <div className="logo">
                    <img src="/images/JLM Cozy cabin.jpg" alt="JLM Cozy Cabin Logo" />
                    <span>JLM Cozy Cabin</span>
                </div>
                <nav className="navigation">
                    <a href="#home">Home</a>
                    <a href="#about">About</a>
                    <a href="#services">Services</a>
                    <a href="#rooms">Rooms</a>
                    <a href="#contact">Contact</a>
                </nav>
                <div className="header-buttons">
                    <Link href="/login" className="login-btn">Log In</Link>
                    <Link href="/signup" className="signup-btn">Get Started</Link>
                </div>
            </header>

            {/* ================= HERO ================= */}
            <section className="hero" id="home">
                <div className="hero-overlay"></div>
                <div className="hero-content">
                    <h1>
                        Affordable &amp; Accessible<br />
                        <span>Living in Ermita,</span> Manila
                    </h1>
                    <p>
                        Your Clean, Comfortable Room Awaits, Anytime, 24/7.
                        Find the perfect space whether you're a student,
                        trainee, seafarer, or OFW.
                    </p>
                    <a href="#rooms" className="hero-btn">Find a Room</a>
                </div>
            </section>

            {/* ================= ABOUT ================= */}
            <section className="about-section" id="about">
                <div className="about-images">
                    <div className="about-image image-1"></div>
                    <div className="about-image image-2"></div>
                    <div className="about-image image-3"></div>
                    <div className="about-image image-4"></div>
                </div>
                <div className="about-content">
                    <p className="section-label">About Us</p>
                    <h2>More Than Just a Room — <span>A Home</span></h2>
                    <p className="about-description">
                        JLM Cozy Cabin provides affordable and comfortable
                        accommodations in Ermita, Manila designed for individuals
                        and groups looking for a clean, secure, and accessible
                        place to stay. Whether for short-term or long-term stay,
                        we offer a home-like environment with convenience and comfort.
                    </p>
                    <div className="about-feature">
                        <div className="feature-icon"><i className="bi bi-geo-alt"></i></div>
                        <div className="feature-text">
                            <h3>Prime Location</h3>
                            <p>Located at 1517 M.H. Del Pilar St., Ermita Manila corner Salas St., near major agencies, business centers, hospitals, and transport access.</p>
                        </div>
                    </div>
                    <div className="about-feature">
                        <div className="feature-icon"><i className="bi bi-shield-check"></i></div>
                        <div className="feature-text">
                            <h3>Safe &amp; Accessible</h3>
                            <p>Close to US Embassy, Manning Agencies, Training Centers, Banks, Hospitals, Robinson Manila, and Manila Bay.</p>
                        </div>
                    </div>
                    <div className="about-feature">
                        <div className="feature-icon"><i className="bi bi-clock"></i></div>
                        <div className="feature-text">
                            <h3>24/7 Open</h3>
                            <p>No check-in time restrictions — guests can check in anytime.</p>
                        </div>
                    </div>
                    <a href="#rooms" className="stay-btn">Start Your Stay</a>
                </div>
            </section>

            {/* ================= AMENITIES ================= */}
            <section className="amenities-section" id="services">
                <div className="amenities-header">
                    <div className="amenities-badge">Premium Amenities</div>
                    <h2>Everything You Need, All Included</h2>
                    <p>From high-speed Wi-Fi and Netflix access to clean, comfortable room amenities — everything is designed to make your stay convenient and stress-free.</p>
                </div>
                <div className="amenities-grid">
                    <div className="amenity-card">
                        <div className="amenity-image image-wifi">
                            <div className="amenity-icon"><i className="bi bi-wifi"></i></div>
                        </div>
                        <div className="amenity-content">
                            <h3>High-Speed Wi-Fi</h3>
                            <p>Reliable internet connection for work, communication, and entertainment.</p>
                        </div>
                    </div>
                    <div className="amenity-card">
                        <div className="amenity-image image-aircon">
                            <div className="amenity-icon"><i className="bi bi-snow"></i></div>
                        </div>
                        <div className="amenity-content">
                            <h3>Air-Conditioned &amp; Fan Rooms</h3>
                            <p>Choose room options that fit your comfort and budget.</p>
                        </div>
                    </div>
                    <div className="amenity-card">
                        <div className="amenity-image image-netflix">
                            <div className="amenity-icon"><i className="bi bi-tv"></i></div>
                        </div>
                        <div className="amenity-content">
                            <h3>Netflix Access</h3>
                            <p>Enjoy entertainment and streaming access during your stay.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ================= ROOMS ================= */}
            <section className="rooms-section" id="rooms">
                <div className="rooms-header">
                    <div className="rooms-badge">Room Listings</div>
                    <h2>Find Your Perfect Room</h2>
                    <p>Browse all available room types and take a virtual tour before you reserve.</p>
                </div>

                <div className="room-status-legend">
                    <div className="status-item"><span className="status-dot available"></span> Available</div>
                    <div className="status-item"><span className="status-dot occupied"></span> Occupied</div>
                    <div className="status-item"><span className="status-dot reserved"></span> Reserved</div>
                </div>

                {/* --- Rooms with virtual tours --- */}
                {tourRooms.length > 0 && (
                    <>
                        <div className="recommended-heading">
                            <div className="recommended-label">
                                <span><i className="bi bi-camera-video-fill"></i></span>
                                Virtual Tours Available
                            </div>
                            <div className="recommended-line"></div>
                        </div>

                        <div className="rooms-grid">
                            {tourRooms.map((room) => (
                                <RoomCard key={room.id} room={room} />
                            ))}
                        </div>
                    </>
                )}

                {/* --- Other rooms --- */}
                {otherRooms.length > 0 && (
                    <>
                        <div className="all-units-heading">
                            <div className="all-units-label">
                                <span><i className="bi bi-grid-fill"></i></span>
                                All Available Rooms
                            </div>
                            <div className="all-units-line"></div>
                        </div>

                        <div className="rooms-grid">
                            {otherRooms.map((room) => (
                                <RoomCard key={room.id} room={room} />
                            ))}
                        </div>
                    </>
                )}
            </section>

            {/* ================= CONTACT ================= */}
            <section className="contact-section" id="contact">
                <div className="contact-container">
                    <div className="contact-info">
                        <div className="contact-badge">Contact Us</div>
                        <h2>Ready to Move In?<span>Let's Talk.</span></h2>
                        <p className="contact-description">
                            Have questions about room availability, pricing, or our facilities?
                            We're happy to help. Reach out or fill in the form.
                        </p>
                        <div className="contact-detail">
                            <div className="contact-icon"><i className="bi bi-telephone"></i></div>
                            <div><small>PHONE</small><p>+63 912 345 6789</p></div>
                        </div>
                        <div className="contact-detail">
                            <div className="contact-icon"><i className="bi bi-envelope"></i></div>
                            <div><small>EMAIL</small><p>info@jlmcozycabin.com</p></div>
                        </div>
                        <div className="contact-detail">
                            <div className="contact-icon"><i className="bi bi-geo-alt"></i></div>
                            <div><small>ADDRESS</small><p>Manila, Philippines</p></div>
                        </div>
                        <div className="contact-detail">
                            <div className="contact-icon"><i className="bi bi-clock"></i></div>
                            <div><small>OFFICE HOURS</small><p>Mon–Sat, 8:00 AM – 8:00 PM</p></div>
                        </div>
                    </div>
                    <div className="contact-form-box">
                        <h3>Send us a Message</h3>
                        <form onSubmit={(e) => e.preventDefault()}>
                            <div className="form-row">
                                <div className="contact-form-group">
                                    <label htmlFor="firstName">First Name</label>
                                    <input type="text" id="firstName" placeholder="Juan" />
                                </div>
                                <div className="contact-form-group">
                                    <label htmlFor="lastName">Last Name</label>
                                    <input type="text" id="lastName" placeholder="Dela Cruz" />
                                </div>
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="email">Email Address</label>
                                <input type="email" id="email" placeholder="juan@example.com" />
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="roomType">Room Type Interest</label>
                                <select id="roomType">
                                    <option value="">Select a room type</option>
                                    <option>Solo Room</option>
                                    <option>Barkada Room</option>
                                    <option>Bedspace</option>
                                </select>
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="message">Message</label>
                                <textarea id="message" rows="3" placeholder="Tell us about yourself..."></textarea>
                            </div>
                            <button type="button" className="send-message-btn">Send Message</button>
                        </form>
                    </div>
                </div>
            </section>

            {/* ================= FOOTER ================= */}
            <footer className="footer">
                <div className="footer-container">
                    <div className="footer-brand">
                        <div className="footer-brand-title">
                            <div className="footer-logo">JLM</div>
                            <h2>JLM Cozy Cabin</h2>
                        </div>
                        <p>Premium boarding house offering comfortable, affordable, and secure living in Manila.</p>
                        <div className="footer-socials">
                            <a href="#" aria-label="Facebook"><i className="bi bi-facebook"></i></a>
                            <a href="#" aria-label="Instagram"><i className="bi bi-instagram"></i></a>
                            <a href="#" aria-label="Twitter"><i className="bi bi-twitter-x"></i></a>
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
        </div>
    );
}

/* =====================================================================
   RoomCard
   ===================================================================== */
function RoomCard({ room }) {
    const statusClass = {
        Available: 'available-status',
        Occupied:  'occupied-status',
        Reserved:  'reserved-status',
    }[room.status] || 'available-status';

    const hasTour = !!room.has_tour && !!room.tour_id;

    return (
        <div className="room-card">
            <div className="room-image">
                {room.thumbnail && (
                    <img
                        src={room.thumbnail}
                        alt={room.room_type}
                        className="room-thumbnail"
                        onError={(e) => { e.target.style.display = 'none'; }}
                    />
                )}
                <div className={`room-status ${statusClass}`}>
                    <i className="bi bi-circle-fill"></i> {room.status}
                </div>
                {hasTour && (
                    <div className="recommended-tag">
                        <i className="bi bi-camera-video-fill"></i> Virtual Tour
                    </div>
                )}
                <div className="views">
                    <i className="bi bi-house-door"></i> Room {room.room_number}
                </div>
            </div>

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
                        <i className="bi bi-house-door"></i>{' '}
                        {room.total_beds} {room.total_beds > 1 ? 'Beds' : 'Bed'}
                    </span>
                    <span><i className="bi bi-droplet"></i> 1 Bath</span>
                </div>

                <div className={`availability-box ${
                    room.status === 'Available' ? 'available-box' :
                    room.status === 'Reserved'  ? 'reserved-box'  : 'occupied-box'
                }`}>
                    <div>
                        <small>
                            <i className="bi bi-calendar3"></i>{' '}
                            {room.status === 'Available' ? 'Available Now' : 'Current Status'}
                        </small>
                        <strong>
                            {room.status === 'Available' ? 'Ready for Move-in' : room.status}
                        </strong>
                    </div>
                </div>

                <div className="room-actions">
                    {hasTour ? (
                        <a
                            href={`/tour/${room.tour_id}`}
                            className="tour-btn"
                            style={{ textDecoration: 'none', display: 'inline-flex', justifyContent: 'center' }}
                        >
                            <i className="bi bi-camera-video"></i> View Virtual Tour
                        </a>
                    ) : (
                        <button className="tour-btn" disabled style={{ opacity: 0.55, cursor: 'not-allowed' }}>
                            <i className="bi bi-camera-video"></i> Tour Coming Soon
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}