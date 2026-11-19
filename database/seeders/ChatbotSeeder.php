<?php
// database/seeders/ChatbotSeeder.php

namespace Database\Seeders;

use App\Models\ChatbotCategory;
use App\Models\ChatbotFaq;
use App\Models\ChatbotSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the landing-page FAQ chatbot.
 * Safe to re-run: rows are matched by slug / question and updated in place.
 *
 *   php artisan db:seed --class=ChatbotSeeder
 *
 * Live tokens available in answers: {{room_types}}, {{room_rates}}, {{room_availability}}
 */
class ChatbotSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- System lines ----------
        $settings = [
            'bot_name'         => 'JLM Cozy Cabin Assistant',
            'bot_subtitle'     => 'How can we help you?',
            'welcome_message'  => "Hello! Welcome to JLM Cozy Cabin!\nI'm here to assist you with your inquiries. Please select a category below.",
            'greeting_reply'   => 'Hello there! 👋 Ask me anything about our rooms, reservations, payments, or house rules — or pick a category below.',
            'fallback_message' => "Sorry, I couldn't find an answer to your question. Please contact the administrator for further assistance.",
            'back_message'     => 'Would you like to explore another category?',
            'closing_message'  => 'Thank you for using the JLM Cozy Cabin Assistant! Have a great day!',
            'contact_phone'    => '+63 912 345 6789',
            'contact_email'    => 'info@jlmcozycabin.com',
        ];
        foreach ($settings as $key => $value) {
            ChatbotSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // ---------- Categories & FAQs ----------
        foreach ($this->content() as $order => $cat) {
            $category = ChatbotCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name'            => $cat['name'],
                    'icon'            => $cat['icon'],
                    'welcome_message' => $cat['welcome'],
                    'button_label'    => $cat['button'][0] ?? null,
                    'button_url'      => $cat['button'][1] ?? null,
                    'keywords'        => $cat['keywords'],
                    'sort_order'      => $order + 1,
                    'is_active'       => true,
                ]
            );

            foreach ($cat['faqs'] as $i => [$question, $answer, $keywords]) {
                ChatbotFaq::updateOrCreate(
                    ['chatbot_category_id' => $category->id, 'question' => $question],
                    ['answer' => $answer, 'keywords' => $keywords, 'sort_order' => $i + 1, 'is_active' => true]
                );
            }
        }
    }

    private function content(): array
    {
        return [
            // ======================================================
            // 1. ROOM INFORMATION
            // ======================================================
            [
                'slug'     => 'room-information',
                'name'     => 'Room Information',
                'icon'     => 'fa-bed',
                'welcome'  => 'Sure! I can help you with information about our rooms, rates, amenities, and availability. What would you like to know?',
                'button'   => ['View Rooms', '#rooms'],
                'keywords' => 'room, rooms, room info, room information, kwarto',
                'faqs'     => [
                    ['What room types are available?',
                        "You can browse the available room types in the Rooms section of our website. Each room includes its details, capacity, and amenities.\n\nWe currently offer:\n{{room_types}}",
                        'room type, types of room, kind of room, room types, solo, barkada, bedspace, options, offer'],
                    ['How much is the room rate?',
                        "Room rates depend on the selected room type. You can view the corresponding rates in the Rooms section.\n\nCurrent daily rates:\n{{room_rates}}",
                        'rate, how much, price, cost, magkano, per day, daily, monthly, rent, cheapest'],
                    ['What are the room amenities?',
                        "Room amenities and facilities are provided in each room's details. Please visit the Rooms section to learn more.\n\nAll tenants enjoy: High-Speed Wi-Fi, air-conditioned & fan room options, Netflix access, 24/7 CCTV security, a shared kitchen, and a laundry area.",
                        'amenities, inclusions, included, facilities, wifi, aircon, netflix, kitchen, laundry, cctv'],
                    ['How can I check room availability?',
                        "Visit the Rooms section to see the current status of each room: Available, Reserved, or Occupied.\n\nRight now:\n{{room_availability}}",
                        'available, availability, vacant, vacancy, still available, bakante, occupied, full'],
                    ['What is the difference between Solo, Barkada, and Bedspace?',
                        "• Solo Room — a private room just for you.\n• Barkada Room — the whole room is booked for your group of friends.\n• Bedspace — you rent a single bed (upper or lower deck) in a shared room.\n\nCapacity and rates for each are shown in the Rooms section.",
                        'difference, solo, barkada, bedspace, shared, private, group, compare'],
                    ['Can I see the room before booking?',
                        'Yes! Rooms with a "Virtual Tour" button in the Rooms section have a 360° tour so you can look around before you reserve. You may also contact us to arrange a visit during office hours (Mon–Sun, 8:00 AM – 8:00 PM).',
                        'see the room, virtual tour, 360, tour, visit the room, look, pictures, photos, ocular, viewing'],
                ],
            ],

            // ======================================================
            // 2. RESERVATION
            // ======================================================
            [
                'slug'     => 'reservation',
                'name'     => 'Reservation',
                'icon'     => 'fa-calendar-check',
                'welcome'  => 'I can guide you through the reservation process and help you understand the booking requirements.',
                'button'   => ['Go to Reservations', '/tenants/reservations'],
                'keywords' => 'reservation, reserve, booking, book',
                'faqs'     => [
                    ['How can I reserve a room?',
                        'Create an account, browse available rooms, select your preferred room, and submit a reservation request.',
                        'how to reserve, how to book, reserve, book a room, booking process, steps, magpareserve'],
                    ['What are the reservation requirements?',
                        'You must provide the required personal information and reservation details. Additional requirements may be requested during the process. Upon move-in, you may be asked to present a valid ID and your reservation confirmation.',
                        'requirements, documents, needed, valid id, what do i need, prepare'],
                    ['Is my reservation automatically approved?',
                        'No. All reservation requests are subject to administrator review and approval. Your request stays under "Pending Verification" until the admin reviews it, and you will be notified once it is approved or rejected.',
                        'automatically approved, approve, approval, confirmed, pending, instant, auto'],
                    ['How can I check my reservation status?',
                        'Log in to your account and visit the Reservations section to view your current reservation status.',
                        'reservation status, check status, track, my reservation, update'],
                    ['Can I change my reserved room?',
                        'Room changes are subject to room availability and administrator approval. Please submit a request through the system.',
                        'change room, change reserved room, switch room, transfer, different room'],
                    ['Do I need an account to book?',
                        'Yes. Click "Sign Up" at the top of the page to create a free account, complete your profile and ID verification, then you can reserve a room.',
                        'account, sign up, signup, register, create account, login, need account'],
                    ['What is the minimum stay?',
                        'The minimum stay is 24 hours (1 full day). You choose your check-in and check-out date and time when you submit your reservation.',
                        'minimum stay, minimum, shortest, how long, one day, overnight stay, short stay'],
                    ['What time is check-in and check-out?',
                        'You pick your preferred check-in and check-out date and time in the reservation form. Just make sure the stay is at least 24 hours and any remaining balance is settled before check-in.',
                        'check in, check-in, checkin, check out, check-out, checkout, arrival, time'],
                    ['Can I cancel my reservation?',
                        'Reservation cancellations and changes to reservation details are subject to room availability, applicable policies, and administrator approval. Please contact the administrator for assistance with your specific booking.',
                        'cancel, cancellation, refund, refundable, back out'],
                    ['What happens if I do not show up?',
                        'Reservations may expire if the requirements are not completed within the specified period or if the tenant fails to arrive on the scheduled move-in date, subject to the applicable reservation conditions.',
                        'no show, did not arrive, expire, expired, late arrival, miss'],
                ],
            ],

            // ======================================================
            // 3. PAYMENT AND BILLING
            // ======================================================
            [
                'slug'     => 'payment-billing',
                'name'     => 'Payment and Billing',
                'icon'     => 'fa-wallet',
                'welcome'  => 'I can help you with payment options, down payments, and billing concerns.',
                'button'   => ['Go to Reservations', '/tenants/reservations'],
                'keywords' => 'payment, billing, pay, bayad, invoice',
                'faqs'     => [
                    ['What payment methods do you accept?',
                        'Reservation payments are made through GCash. Enter your GCash reference details and upload your proof of payment in the reservation form so the admin can verify it.',
                        'payment method, mode of payment, gcash, how to pay, pay, bank, cash, accept'],
                    ['Do I need to pay a down payment?',
                        'You can choose either a 50% down payment or full payment when you reserve. If you choose the 50% down payment, the remaining 50% balance must be settled before your scheduled check-in date and time.',
                        'down payment, downpayment, dp, deposit, partial, 50%, half, full payment'],
                    ['When should I pay the remaining balance?',
                        'The remaining balance must be settled before your scheduled check-in date and time. You will receive reminders while a balance is still outstanding.',
                        'remaining balance, balance, settle, due, deadline, when to pay, outstanding'],
                    ['What if I have an unpaid balance?',
                        'Tenants with an outstanding balance cannot use tenant services (maintenance, room change, stay extension) until the balance is settled. Please settle it as soon as possible to avoid issues with your stay.',
                        'unpaid, outstanding, overdue, late payment, not paid, locked'],
                    ['How do I view my billing and receipts?',
                        'Log in to your tenant account and open your Dashboard or History to see your invoices, payments, and remaining balance.',
                        'receipt, invoice, billing, history, statement, proof, view bill'],
                ],
            ],

            // ======================================================
            // 4. HOUSE RULES AND POLICIES
            // ======================================================
            [
                'slug'     => 'house-rules',
                'name'     => 'House Rules and Policies',
                'icon'     => 'fa-scroll',
                'welcome'  => 'Here are some important rules and policies to help maintain a safe and comfortable environment for all tenants.',
                'button'   => ['View Full Policies', 'chat:policies'],
                'keywords' => 'rules, policy, policies, house rules, regulations, bawal',
                'faqs'     => [
                    ['What are the quiet hours?',
                        'Quiet hours are observed from 10:00 PM to 7:00 AM.',
                        'quiet hours, quiet, noise, noisy, loud, ingay, sleep'],
                    ['What are the visiting hours?',
                        'Visitors are allowed from 8:00 AM to 9:00 PM and must register at the front desk and present a valid ID.',
                        'visiting hours, visitor, visit, guest, bisita, friends visit'],
                    ['Are overnight visitors allowed?',
                        'No. Overnight stays by non-boarders are not permitted.',
                        'overnight visitor, overnight, sleep over, sleepover, stay the night, non-boarder'],
                    ['Is smoking or vaping allowed?',
                        'No. Smoking and vaping are prohibited inside the boarding house. Violations are subject to a ₱500 penalty.',
                        'smoking, smoke, vape, vaping, cigarette, yosi, penalty'],
                    ['Are pets allowed?',
                        'Please contact the administrator to ask about pets before booking, as this depends on the room and house policies.',
                        'pets, pet, dog, cat, animal, aso, pusa'],
                    ['Is there parking available?',
                        'Please contact the administrator to ask about parking availability before your move-in.',
                        'parking, park, car, motorcycle, motor, vehicle'],
                ],
            ],

            // ======================================================
            // 5. SERVICES AND REQUESTS
            // ======================================================
            [
                'slug'     => 'services-requests',
                'name'     => 'Services and Requests',
                'icon'     => 'fa-screwdriver-wrench',
                'welcome'  => 'I can help you understand the services available to current tenants, including maintenance concerns, room changes, and stay extensions.',
                'button'   => ['Go to Services', '/tenants/services/maintenance'],
                'keywords' => 'services, service, requests, request, tenant services',
                'faqs'     => [
                    ['How can I request room maintenance?',
                        'Log in to your tenant account, go to Services, select Maintenance Request, and provide the details of your concern.',
                        'maintenance, repair, broken, sira, fix, not working, leak, aircon problem'],
                    ['How can I request a room change?',
                        'Go to Services, select Room Change Request, choose an available room, and submit your reason and required payment proof.',
                        'room change request, request a room change, change room, transfer room, lipat'],
                    ['How can I extend my stay?',
                        'Go to Services, select Stay Extension, provide your preferred extension date, reason, and payment details, then submit your request.',
                        'extend, extension, extend my stay, stay longer, longer stay'],
                    ['How can I check my request status?',
                        'Visit the Services section of your tenant account to check the status of your submitted requests.',
                        'request status, my request, track request, service status'],
                ],
            ],

            // ======================================================
            // 6. CONTACT & LOCATION (extra)
            // ======================================================
            [
                'slug'     => 'contact-location',
                'name'     => 'Contact and Location',
                'icon'     => 'fa-location-dot',
                'welcome'  => 'Here is how you can reach us or find our place.',
                'button'   => ['Contact Us', '#contact'],
                'keywords' => 'contact, location, address, where, reach',
                'faqs'     => [
                    ['Where are you located?',
                        'We are located at 1517 M.H. Del Pilar St., Ermita, Manila.',
                        'location, located, address, where, saan, directions, map'],
                    ['How can I contact the administrator?',
                        'You can reach us at +63 912 345 6789 or email info@jlmcozycabin.com. Our office hours are Mon–Sun, 8:00 AM – 8:00 PM.',
                        'contact, phone, number, email, call, text, admin, administrator, talk to someone, human'],
                    ['What are your office hours?',
                        'Our office is open Mon–Sun, 8:00 AM – 8:00 PM.',
                        'office hours, open, opening, business hours, schedule'],
                ],
            ],
        ];
    }
}
