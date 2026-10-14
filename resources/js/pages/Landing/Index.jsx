// resources/js/Pages/Landing/Index.jsx
import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Index() {
    const [tourOpen, setTourOpen] = useState(false);
    const [tourTitle, setTourTitle] = useState('Room Tour');

    const openVirtualTour = (roomName) => {
        setTourTitle(roomName);
        setTourOpen(true);
    };
    const closeVirtualTour = () => setTourOpen(false);

    // Close modal with Escape key
    React.useEffect(() => {
        const handler = (e) => {
            if (e.key === 'Escape') closeVirtualTour();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, []);

    return (
        <div className="landing-page">
            <Head title="JLM Cozy Cabin" />

            {/* HEADER */}
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

            {/* HERO */}
            <section className="hero" id="home">
                <div className="hero-overlay"></div>
                <div className="hero-content">
                    <h1>
                        Affordable & Accessible
                        <br />
                        <span>Living in Ermita,</span> Manila
                    </h1>
                    <p>
                        Your Clean, Comfortable Room Awaits, Anytime, 24/7.
                        Find the perfect space whether you're a student,
                        trainee, seafarer, or OFW.
                    </p>
                    <a href="#" className="hero-btn">Find a Room</a>
                </div>
            </section>

            {/* ABOUT US */}
            <section className="about-section" id="about">
                <div className="about-images">
                    <div className="about-image image-1"></div>
                    <div className="about-image image-2"></div>
                    <div className="about-image image-3"></div>
                    <div className="about-image image-4"></div>
                </div>

                <div className="about-content">
                    <p className="section-label">About Us</p>
                    <h2>
                        More Than Just a Room —
                        <span>A Home</span>
                    </h2>
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
                            <p>
                                Located at 1517 M.H. Del Pilar St., Ermita Manila
                                corner Salas St., near major agencies, business
                                centers, hospitals, and transport access.
                            </p>
                        </div>
                    </div>

                    <div className="about-feature">
                        <div className="feature-icon"><i className="bi bi-shield-check"></i></div>
                        <div className="feature-text">
                            <h3>Safe & Accessible</h3>
                            <p>
                                Close to US Embassy, Manning Agencies, Training
                                Centers, Banks, Hospitals, Robinson Manila, and Manila Bay.
                            </p>
                        </div>
                    </div>

                    <div className="about-feature">
                        <div className="feature-icon"><i className="bi bi-clock"></i></div>
                        <div className="feature-text">
                            <h3>24/7 Open</h3>
                            <p>No check-in time restrictions — guests can check in anytime.</p>
                        </div>
                    </div>

                    <a href="#" className="stay-btn">Start Your Stay</a>
                </div>
            </section>

            {/* AMENITIES */}
            <section className="amenities-section" id="services">
                <div className="amenities-header">
                    <div className="amenities-badge">Premium Amenities</div>
                    <h2>Everything You Need, All Included</h2>
                    <p>
                        From high-speed Wi-Fi and Netflix access to clean,
                        comfortable room amenities — everything is designed
                        to make your stay convenient and stress-free.
                    </p>
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
                            <h3>Air-Conditioned & Fan Rooms</h3>
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

            {/* ROOMS */}
            <section className="rooms-section" id="rooms">
                <div className="rooms-header">
                    <div className="rooms-badge">Room Listings</div>
                    <h2>Find Your Perfect Room</h2>
                    <p>
                        Browse all available room types — solo, barkada, and bedspace —
                        and reserve your spot in just a few clicks.
                    </p>
                </div>

                <div className="room-status-legend">
                    <div className="status-item"><span className="status-dot available"></span> Available</div>
                    <div className="status-item"><span className="status-dot occupied"></span> Occupied</div>
                    <div className="status-item"><span className="status-dot reserved"></span> Reserved</div>
                </div>

                <div className="recommended-heading">
                    <div className="recommended-label">
                        <span><i className="bi bi-star-fill"></i></span>
                        Recommended for You
                    </div>
                    <div className="recommended-line"></div>
                </div>

                <div className="rooms-grid">
                    {/* CARD 1 */}
                    <div className="room-card">
                        <div className="room-image">
                            <div className="room-status occupied-status">
                                <i className="bi bi-circle-fill"></i> Occupied
                            </div>
                            <div className="most-viewed">
                                <i className="bi bi-arrow-up-right"></i> Most Viewed
                            </div>
                            <div className="recommended-tag">
                                <i className="bi bi-star-fill"></i> Recommended
                            </div>
                            <div className="views"><i className="bi bi-eye"></i> 2,134 views</div>
                        </div>
                        <div className="room-info">
                            <div className="room-title-row">
                                <div>
                                    <h3>Solo Room - Standard</h3>
                                    <p className="room-type">Standard</p>
                                </div>
                                <div className="room-price">₱600<span>per day</span></div>
                            </div>
                            <div className="room-details">
                                <span><i className="bi bi-house-door"></i> 1 Bed</span>
                                <span><i className="bi bi-droplet"></i> 1 Bath</span>
                            </div>
                            <div className="availability-box occupied-box">
                                <div>
                                    <small><i className="bi bi-calendar3"></i> Next Estimated Availability</small>
                                    <strong>May 1, 2026</strong>
                                </div>
                            </div>
                            <div className="room-actions">
                                <button className="tour-btn" onClick={() => openVirtualTour('Solo Room - Standard')}>
                                    <i className="bi bi-camera-video"></i> View Virtual Tour
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* CARD 2 */}
                    <div className="room-card">
                        <div className="room-image">
                            <div className="room-status available-status">
                                <i className="bi bi-circle-fill"></i> Available
                            </div>
                            <div className="recommended-tag">
                                <i className="bi bi-star-fill"></i> Recommended
                            </div>
                            <div className="views"><i className="bi bi-eye"></i> 1,856 views</div>
                        </div>
                        <div className="room-info">
                            <div className="room-title-row">
                                <div>
                                    <h3>Solo Room - Deluxe</h3>
                                    <p className="room-type">Deluxe</p>
                                </div>
                                <div className="room-price">₱950<span>per day</span></div>
                            </div>
                            <div className="room-details">
                                <span><i className="bi bi-house-door"></i> 1 Bed</span>
                                <span><i className="bi bi-droplet"></i> 1 Bath</span>
                            </div>
                            <div className="availability-box available-box">
                                <div>
                                    <small><i className="bi bi-calendar3"></i> Available Now</small>
                                    <strong>Ready for Move-in</strong>
                                </div>
                            </div>
                            <div className="room-actions">
                                <button className="tour-btn" onClick={() => openVirtualTour('Solo Room - Deluxe')}>
                                    <i className="bi bi-camera-video"></i> View Virtual Tour
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* CARD 3 */}
                    <div className="room-card">
                        <div className="room-image">
                            <div className="room-status available-status">
                                <i className="bi bi-circle-fill"></i> Available
                            </div>
                            <div className="recommended-tag">
                                <i className="bi bi-star-fill"></i> Recommended
                            </div>
                            <div className="views"><i className="bi bi-eye"></i> 1,245 views</div>
                        </div>
                        <div className="room-info">
                            <div className="room-title-row">
                                <div>
                                    <h3>Bedspace - Premium</h3>
                                    <p className="room-type">Premium</p>
                                </div>
                                <div className="room-price">₱450<span>per day</span></div>
                            </div>
                            <div className="room-details">
                                <span><i className="bi bi-house-door"></i> 1 Bed</span>
                                <span><i className="bi bi-droplet"></i> 1 Bath</span>
                            </div>
                            <div className="availability-box available-box">
                                <div>
                                    <small><i className="bi bi-calendar3"></i> Available Now</small>
                                    <strong>Ready for Move-in</strong>
                                </div>
                            </div>
                            <div className="room-actions">
                                <button className="tour-btn" onClick={() => openVirtualTour('Bedspace - Premium')}>
                                    <i className="bi bi-camera-video"></i> View Virtual Tour
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="all-units-heading">
                    <div className="all-units-label">
                        <span><i className="bi bi-grid-fill"></i></span>
                        All Available Rooms
                    </div>
                    <div className="all-units-line"></div>
                </div>

                <div className="rooms-grid">
                    {[
                        { num: 202, name: 'Solo Room - Standard', type: 'Standard', price: 600, status: 'occupied', avail: 'May 1, 2026', availLabel: 'Next Estimated Availability', views: 2134 },
                        { num: 203, name: 'Solo Room - Deluxe', type: 'Deluxe', price: 950, status: 'available', avail: 'Ready for Move-in', availLabel: 'Available Now', views: 1856 },
                        { num: 204, name: 'Barkada Room - Standard', type: 'Standard', price: 200, status: 'occupied', avail: 'April 25, 2026', availLabel: 'Next Estimated Availability', views: 993 },
                        { num: 301, name: 'Barkada Room - Premium', type: 'Premium', price: 400, status: 'reserved', avail: 'May 15, 2026', availLabel: 'Next Estimated Availability', views: 569 },
                        { num: 302, name: 'Bedspace - Standard', type: 'Standard', price: 300, status: 'available', avail: 'Ready for Move-in', availLabel: 'Available Now', views: 875 },
                    ].map((room, i) => (
                        <div className="room-card" key={i}>
                            <div className="room-image">
                                <div className={`room-status ${room.status === 'occupied' ? 'occupied-status' : room.status === 'available' ? 'available-status' : 'reserved-status'}`}>
                                    <i className="bi bi-circle-fill"></i> {room.status.charAt(0).toUpperCase() + room.status.slice(1)}
                                </div>
                                <div className="views"><i className="bi bi-eye"></i> {room.views.toLocaleString()} views</div>
                            </div>
                            <div className="room-info">
                                <div className="room-title-row">
                                    <div>
                                        <h3>{room.name}</h3>
                                        <p className="room-type">{room.type}</p>
                                    </div>
                                    <div className="room-price">₱{room.price}<span>per day</span></div>
                                </div>
                                <div className="room-details">
                                    <span><i className="bi bi-house-door"></i> {room.name.includes('Barkada') ? '4 Beds' : '1 Bed'}</span>
                                    <span><i className="bi bi-droplet"></i> 1 Bath</span>
                                </div>
                                <div className={`availability-box ${room.status === 'available' ? 'available-box' : room.status === 'reserved' ? 'reserved-box' : 'occupied-box'}`}>
                                    <div>
                                        <small><i className="bi bi-calendar3"></i> {room.availLabel}</small>
                                        <strong>{room.avail}</strong>
                                    </div>
                                </div>
                                <div className="room-actions">
                                    <button className="tour-btn" onClick={() => openVirtualTour(room.name)}>
                                        <i className="bi bi-camera-video"></i> View Virtual Tour
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* CONTACT */}
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
                                    <input type="text" id="firstName" name="firstName" placeholder="Juan" />
                                </div>
                                <div className="contact-form-group">
                                    <label htmlFor="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" placeholder="Dela Cruz" />
                                </div>
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="juan@example.com" />
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="roomType">Room Type Interest</label>
                                <select id="roomType" name="roomType">
                                    <option value="">Select a room type</option>
                                    <option value="solo-standard">Solo Room - Standard</option>
                                    <option value="solo-deluxe">Solo Room - Deluxe</option>
                                    <option value="barkada-standard">Barkada Room - Standard</option>
                                    <option value="barkada-premium">Barkada Room - Premium</option>
                                    <option value="bedspace-standard">Bedspace - Standard</option>
                                    <option value="bedspace-premium">Bedspace - Premium</option>
                                </select>
                            </div>
                            <div className="contact-form-group">
                                <label htmlFor="message">Message</label>
                                <textarea id="message" name="message" rows="3" placeholder="Tell us about yourself and what you're looking for..."></textarea>
                            </div>
                            <button type="button" className="send-message-btn">Send Message</button>
                        </form>
                    </div>
                </div>
            </section>

            {/* FOOTER */}
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
                        <a href="#"><span>›</span> Home</a>
                        <a href="#"><span>›</span> About</a>
                        <a href="#"><span>›</span> Services</a>
                        <a href="#"><span>›</span> Rooms</a>
                        <a href="#"><span>›</span> Contact</a>
                    </div>

                    <div className="footer-column">
                        <h3>Room Types</h3>
                        <a href="#"><span>›</span> Solo Room – Standard</a>
                        <a href="#"><span>›</span> Solo Room – Deluxe</a>
                        <a href="#"><span>›</span> Barkada Room</a>
                        <a href="#"><span>›</span> Bedspace Unit</a>
                    </div>

                    <div className="footer-column">
                        <h3>Resources</h3>
                        <a href="#"><span>›</span> FAQs</a>
                        <a href="#"><span>›</span> Privacy Policy</a>
                        <a href="#"><span>›</span> Terms of Service</a>
                        <a href="#"><span>›</span> Tenant Portal</a>
                    </div>
                </div>

                <div className="footer-bottom">
                    <p>© 2026 JLM Cozy Cabin. All rights reserved.</p>
                </div>
            </footer>

            {/* VIRTUAL TOUR MODAL */}
            <div className={`virtual-tour-modal ${tourOpen ? 'active' : ''}`} onClick={(e) => e.target === e.currentTarget && closeVirtualTour()}>
                <div className="virtual-tour-box">
                    <button className="modal-close" onClick={closeVirtualTour}>×</button>
                    <div className="modal-header">
                        <p>Virtual Tour</p>
                        <h2>{tourTitle}</h2>
                    </div>
                    <div className="virtual-tour-image">
                        <div className="tour-image-placeholder"><span>Room Preview</span></div>
                    </div>
                    <div className="virtual-tour-details">
                        <div className="tour-detail-item"><span>Rental Fee</span><strong>₱600</strong><small>per day</small></div>
                        <div className="tour-detail-item"><span>Capacity</span><strong>1</strong><small>person</small></div>
                        <div className="tour-detail-item"><span>Size</span><strong>—</strong><small>sq ft</small></div>
                        <div className="tour-detail-item"><span>Floor</span><strong>—</strong><small>level</small></div>
                    </div>
                    <div className="tour-features">
                        <h3>ROOM FEATURES</h3>
                        <div className="tour-feature-tags">
                            <span>Aircon</span><span>Fan</span><span>Private CR</span><span>TV</span><span>WiFi</span>
                        </div>
                    </div>
                    <div className="tour-modal-actions">
                        <button className="tour-modal-close-btn" onClick={closeVirtualTour}>Close</button>
                    </div>
                </div>
            </div>
        </div>
    );
}