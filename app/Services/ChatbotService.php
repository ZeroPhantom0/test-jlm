<?php
// app/Services/ChatbotService.php

namespace App\Services;

use App\Models\ChatbotCategory;
use App\Models\ChatbotFaq;
use App\Models\ChatbotSetting;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * Rule-based FAQ chatbot.
 *
 * 1. Normalises the visitor's message (lower-case, no punctuation,
 *    synonyms / Taglish words mapped to a canonical word, light stemming).
 * 2. Scores every active FAQ against its keywords + question words.
 * 3. Returns the best FAQ above a threshold, a category menu when only a
 *    category matches, or the fallback line.
 *
 * Answers can include live tokens that are filled from the database:
 *   {{room_types}}, {{room_rates}}, {{room_availability}}
 */
class ChatbotService
{
    /** Minimum score for an FAQ to be returned as the answer. */
    private const THRESHOLD = 2;

    private const STOPWORDS = [
        'a', 'an', 'the', 'i', 'me', 'my', 'we', 'our', 'you', 'your', 'is', 'are',
        'am', 'be', 'to', 'of', 'in', 'on', 'at', 'for', 'and', 'or', 'it', 'its',
        'do', 'does', 'did', 'can', 'could', 'will', 'would', 'should', 'there',
        'what', 'how', 'when', 'where', 'which', 'who', 'why', 'this', 'that',
        'with', 'about', 'please', 'pls', 'po', 'ba', 'ang', 'ng', 'sa', 'na',
        'ko', 'ako', 'mo', 'yung', 'any', 'have', 'has', 'get', 'know', 'tell',
        'want', 'like', 'just', 'ask', 'question', 'hello', 'hi', 'so', 'if',
    ];

    /** Word → canonical word (handles synonyms, Taglish and common typos). */
    private const SYNONYMS = [
        'price' => 'rate', 'prices' => 'rate', 'cost' => 'rate', 'costs' => 'rate',
        'magkano' => 'rate', 'presyo' => 'rate', 'rent' => 'rate', 'rental' => 'rate',
        'fee' => 'rate', 'fees' => 'rate', 'rates' => 'rate', 'charge' => 'rate',
        'expensive' => 'rate', 'cheap' => 'rate', 'cheapest' => 'rate', 'budget' => 'rate',
        'book' => 'reserve', 'booking' => 'reserve', 'reservation' => 'reserve',
        'reservations' => 'reserve', 'reserved' => 'reserve', 'reserving' => 'reserve',
        'magpareserve' => 'reserve', 'mag-book' => 'reserve',
        'kwarto' => 'room', 'kuwarto' => 'room', 'rooms' => 'room', 'unit' => 'room',
        'bed' => 'bedspace', 'beds' => 'bedspace', 'bedspacer' => 'bedspace',
        'vacant' => 'available', 'vacancy' => 'available', 'availability' => 'available',
        'bakante' => 'available',
        'pay' => 'payment', 'paying' => 'payment', 'payments' => 'payment', 'bayad' => 'payment',
        'magbayad' => 'payment', 'paid' => 'payment', 'billing' => 'payment', 'bill' => 'payment',
        'invoice' => 'payment', 'gcash' => 'gcash', 'g-cash' => 'gcash',
        'downpayment' => 'down', 'dp' => 'down', 'deposit' => 'down', 'partial' => 'down',
        'refund' => 'cancel', 'refundable' => 'cancel', 'cancellation' => 'cancel', 'cancelled' => 'cancel',
        'wifi' => 'wifi', 'wi-fi' => 'wifi', 'internet' => 'wifi',
        'aircon' => 'aircon', 'ac' => 'aircon', 'airconditioned' => 'aircon', 'aircondition' => 'aircon',
        'amenity' => 'amenities', 'facility' => 'amenities', 'facilities' => 'amenities', 'inclusions' => 'amenities',
        'visitor' => 'visit', 'visitors' => 'visit', 'guest' => 'visit', 'guests' => 'visit', 'bisita' => 'visit',
        'smoke' => 'smoking', 'smoker' => 'smoking', 'vape' => 'smoking', 'vaping' => 'smoking', 'yosi' => 'smoking',
        'pet' => 'pets', 'dog' => 'pets', 'cat' => 'pets', 'aso' => 'pets', 'pusa' => 'pets',
        'noise' => 'quiet', 'noisy' => 'quiet', 'ingay' => 'quiet',
        'repair' => 'maintenance', 'broken' => 'maintenance', 'sira' => 'maintenance', 'fix' => 'maintenance',
        'transfer' => 'change', 'move' => 'change', 'lipat' => 'change', 'switch' => 'change',
        'extend' => 'extension', 'extending' => 'extension', 'longer' => 'extension',
        'located' => 'location', 'address' => 'location', 'saan' => 'location', 'directions' => 'location',
        'phone' => 'contact', 'number' => 'contact', 'email' => 'contact', 'call' => 'contact',
        'checkin' => 'checkin', 'check-in' => 'checkin', 'checkout' => 'checkout', 'check-out' => 'checkout',
        'signup' => 'account', 'register' => 'account', 'registration' => 'account', 'login' => 'account',
        'id' => 'id', 'ids' => 'id', 'requirement' => 'requirements', 'documents' => 'requirements',
        'parking' => 'parking', 'park' => 'parking', 'car' => 'parking', 'motor' => 'parking',
        'laundry' => 'laundry', 'labada' => 'laundry', 'wash' => 'laundry',
        'kitchen' => 'kitchen', 'cook' => 'kitchen', 'cooking' => 'kitchen', 'luto' => 'kitchen',
        'security' => 'security', 'safe' => 'security', 'cctv' => 'security',
        'curfew' => 'curfew', 'gate' => 'curfew',
        'minimum' => 'minimum', 'shortest' => 'minimum', 'overnight' => 'overnight',
        'barkada' => 'barkada', 'group' => 'barkada', 'friends' => 'barkada',
        'solo' => 'solo', 'private' => 'solo', 'single' => 'solo',
        'tour' => 'tour', '360' => 'tour', 'virtual' => 'tour',
        'status' => 'status', 'approved' => 'approve', 'approval' => 'approve', 'confirm' => 'approve',
        'confirmed' => 'approve', 'pending' => 'approve',
    ];

    private const GREETINGS = ['hi', 'hello', 'hey', 'helo', 'hii', 'good morning', 'good afternoon',
        'good evening', 'magandang umaga', 'magandang hapon', 'magandang gabi', 'kumusta', 'kamusta', 'yo'];

    private const CLOSINGS = ['thanks', 'thank you', 'thank u', 'ty', 'salamat', 'bye', 'goodbye',
        'that is all', "that's all", 'okay thanks', 'ok thanks', 'done', 'no more'];

    // ============================================================
    // PUBLIC API
    // ============================================================

    /** Data the widget needs on open: settings + categories with questions. */
    public function bootstrap(): array
    {
        $settings = ChatbotSetting::map();

        $categories = ChatbotCategory::active()
            ->with(['faqs' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn (ChatbotCategory $c) => $this->categoryPayload($c))
            ->values();

        return [
            'settings'   => $settings,
            'categories' => $categories,
        ];
    }

    /** Answer a single FAQ chosen from the menu. */
    public function answerFaq(ChatbotFaq $faq): array
    {
        $faq->increment('hits');
        $faq->loadMissing('category');

        return $this->answerPayload($faq);
    }

    /** Category chosen from the menu (or typed). */
    public function openCategory(ChatbotCategory $category): array
    {
        $category->load(['faqs' => fn ($q) => $q->where('is_active', true)]);

        return [
            'type'     => 'category',
            'text'     => $category->welcome_message,
            'category' => $this->categoryPayload($category),
            'button'   => $this->buttonFor($category),
        ];
    }

    /** Free-text question → best answer. */
    public function ask(string $message): array
    {
        $settings = ChatbotSetting::map();
        $raw      = $this->normalise($message);

        if ($raw === '') {
            return $this->fallback($settings);
        }

        // Greetings / closings first — but only for short messages so that
        // "hi, how much is a room?" still goes through FAQ matching.
        $wordCount = count(explode(' ', $raw));
        if ($wordCount <= 4 && $this->containsAny($raw, self::CLOSINGS)) {
            return ['type' => 'closing', 'text' => $settings['closing_message'] ?? 'Thank you!'];
        }
        if ($wordCount <= 3 && $this->containsAny($raw, self::GREETINGS)) {
            return [
                'type' => 'greeting',
                'text' => $settings['greeting_reply'] ?? ($settings['welcome_message'] ?? 'Hello!'),
            ];
        }

        // A short message that just names a category ("billing", "house rules")
        // opens that category's menu instead of guessing one question.
        if ($wordCount <= 3 && ($exact = $this->exactCategory($raw))) {
            return $this->openCategory($exact);
        }

        $tokens = $this->tokens($raw);

        $faqs =ChatbotFaq::with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->get();

        $scored = $faqs
            ->map(fn (ChatbotFaq $f) => ['faq' => $f, 'score' => $this->scoreFaq($f, $raw, $tokens)])
            ->filter(fn ($row) => $row['score'] >= self::THRESHOLD)
            ->sortByDesc('score')
            ->values();

        if ($scored->isNotEmpty()) {
            /** @var ChatbotFaq $best */
            $best = $scored->first()['faq'];
            $best->increment('hits');

            $payload = $this->answerPayload($best);
            $payload['related'] = $scored->slice(1, 3)
                ->map(fn ($row) => ['id' => $row['faq']->id, 'question' => $row['faq']->question])
                ->values();

            return $payload;
        }

        // No single FAQ, but the message clearly points to a category
        $category = $this->matchCategory($raw, $tokens);
        if ($category) {
            return $this->openCategory($category);
        }

        return $this->fallback($settings);
    }

    // ============================================================
    // MATCHING
    // ============================================================

    private function normalise(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(['’', '‘'], "'", $text);
        $text = preg_replace('/[^\p{L}\p{N}\s\'\-₱]/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** Canonical, stop-word-free tokens. */
    private function tokens(string $normalised): array
    {
        $out = [];
        foreach (explode(' ', $normalised) as $word) {
            $word = trim($word, "'-");
            if ($word === '' || in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            $out[] = $this->canonical($word);
        }

        return array_values(array_unique($out));
    }

    private function canonical(string $word): string
    {
        // Map synonyms, then always stem the result so keywords and the
        // visitor's words end up in exactly the same form.
        $mapped = self::SYNONYMS[$word] ?? self::SYNONYMS[$this->stem($word)] ?? $this->typoFix($word) ?? $word;

        return $this->stem($mapped);
    }

    /** Map a misspelt long word to a known vocabulary word ("reservaton" → reserve). */
    private function typoFix(string $word): ?string
    {
        $len = mb_strlen($word);
        if ($len < 7) {
            return null;
        }

        foreach (self::SYNONYMS as $known => $target) {
            if (abs(mb_strlen($known) - $len) <= 1 && levenshtein($known, $word) === 1) {
                return $target;
            }
        }

        return null;
    }

    /** Light stemming: plurals and -ing. */
    private function stem(string $word): string
    {
        $word = preg_replace('/(?<=\w{2})ies$/u', 'y', $word);

        return preg_replace('/(?<=\w{3})(ing|es|s)$/u', '', $word);
    }

    private function scoreFaq(ChatbotFaq $faq, string $raw, array $tokens): float
    {
        $score = 0.0;
        $credited = []; // each canonical word / phrase scores once per FAQ

        foreach ($faq->keywordList() as $keyword) {
            if (str_contains($keyword, ' ')) {
                $parts = $this->tokens($keyword);
                $key   = implode(' ', $parts);
                if (! $parts || isset($credited[$key])) {
                    continue;
                }
                // Phrase keyword — strong signal when the exact phrase appears,
                // slightly weaker when all its words appear in any order
                if (str_contains($raw, $keyword)) {
                    $score += 2 + count($parts) * 1.5;
                    $credited[$key] = true;
                } elseif (count($parts) > 1 && ! array_diff($parts, $tokens)) {
                    $score += 2 + count($parts) * 0.5;
                    $credited[$key] = true;
                }
                continue;
            }

            $kw = $this->canonical($keyword);
            if (isset($credited[$kw])) {
                continue;
            }
            if (in_array($kw, $tokens, true)) {
                $score += 2;
                $credited[$kw] = true;
            } elseif (mb_strlen($kw) >= 5 && $this->fuzzyHit($kw, $tokens)) {
                $score += 2; // tolerate a typo ("ammenities")
                $credited[$kw] = true;
            }
        }

        // Small bonus for overlap with the FAQ's own question wording
        $questionTokens = $this->tokens($this->normalise($faq->question));
        $score += count(array_intersect($questionTokens, $tokens)) * 0.75;

        return $score;
    }

    private function fuzzyHit(string $keyword, array $tokens): bool
    {
        foreach ($tokens as $t) {
            if (mb_strlen($t) >= 4 && levenshtein($keyword, $t) <= 1 + (int) (mb_strlen($keyword) >= 9)) {
                return true;
            }
        }

        return false;
    }

    private function matchCategory(string $raw, array $tokens): ?ChatbotCategory
    {
        $best = null;
        $bestScore = 0;

        foreach (ChatbotCategory::active()->get() as $category) {
            $score = 0;
            $keywords = array_filter(array_map('trim', explode(',', mb_strtolower((string) $category->keywords))));
            $keywords[] = mb_strtolower($category->name);

            foreach ($keywords as $kw) {
                if (str_contains($raw, $kw)) {
                    $score += 2;
                } elseif (! str_contains($kw, ' ') && in_array($this->canonical($kw), $tokens, true)) {
                    $score += 1;
                }
            }

            if ($score > $bestScore) {
                $best = $category;
                $bestScore = $score;
            }
        }

        return $bestScore >= 1 ? $best : null;
    }

    private function exactCategory(string $raw): ?ChatbotCategory
    {
        return ChatbotCategory::active()->get()->first(function (ChatbotCategory $c) use ($raw) {
            $names = array_map('trim', explode(',', mb_strtolower((string) $c->keywords)));
            $names[] = mb_strtolower($c->name);

            return in_array($raw, $names, true);
        });
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (preg_match('/(^|\s)' . preg_quote($n, '/') . '($|\s)/u', $haystack)) {
                return true;
            }
        }

        return false;
    }

    // ============================================================
    // PAYLOADS
    // ============================================================

    private function answerPayload(ChatbotFaq $faq): array
    {
        return [
            'type'     => 'answer',
            'faq_id'   => $faq->id,
            'question' => $faq->question,
            'text'     => $this->fillLiveTokens($faq->answer),
            'category' => $faq->category ? [
                'id'   => $faq->category->id,
                'slug' => $faq->category->slug,
                'name' => $faq->category->name,
            ] : null,
            'button'  => $faq->category ? $this->buttonFor($faq->category) : null,
            'related' => [],
        ];
    }

    private function categoryPayload(ChatbotCategory $c): array
    {
        return [
            'id'              => $c->id,
            'slug'            => $c->slug,
            'name'            => $c->name,
            'icon'            => $c->icon,
            'welcome_message' => $c->welcome_message,
            'button'          => $this->buttonFor($c),
            'questions'       => $c->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question])->values(),
        ];
    }

    private function buttonFor(ChatbotCategory $c): ?array
    {
        return $c->button_label && $c->button_url
            ? ['label' => $c->button_label, 'url' => $c->button_url]
            : null;
    }

    private function fallback(array $settings): array
    {
        return [
            'type' => 'fallback',
            'text' => $settings['fallback_message']
                ?? "Sorry, I couldn't find an answer to your question. Please contact the administrator for further assistance.",
        ];
    }

    // ============================================================
    // LIVE DATABASE TOKENS
    // ============================================================

    private function fillLiveTokens(string $answer): string
    {
        if (! str_contains($answer, '{{')) {
            return $answer;
        }

        $rooms = null;
        $getRooms = function () use (&$rooms): Collection {
            return $rooms ??= Room::query()
                ->select('room_type', 'daily_rent', 'status', 'total_beds')
                ->get();
        };

        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($getRooms) {
            return match ($m[1]) {
                'room_types'        => $this->liveRoomTypes($getRooms()),
                'room_rates'        => $this->liveRoomRates($getRooms()),
                'room_availability' => $this->liveAvailability($getRooms()),
                default             => '',
            };
        }, $answer);
    }

    private function liveRoomTypes(Collection $rooms): string
    {
        if ($rooms->isEmpty()) {
            return '';
        }

        return $rooms->groupBy('room_type')
            ->map(function ($group, $type) {
                $beds = $group->max('total_beds');
                $cap  = $type === 'Solo Room' ? 'private room' : "up to {$beds} beds";
                return "• {$type} ({$group->count()} room" . ($group->count() > 1 ? 's' : '') . ", {$cap})";
            })
            ->implode("\n");
    }

    private function liveRoomRates(Collection $rooms): string
    {
        if ($rooms->isEmpty()) {
            return '';
        }

        return $rooms->groupBy('room_type')
            ->map(function ($group, $type) {
                $min = (float) $group->min('daily_rent');
                $max = (float) $group->max('daily_rent');
                $range = $min === $max
                    ? '₱' . number_format($min, 2)
                    : '₱' . number_format($min, 2) . ' – ₱' . number_format($max, 2);
                $unit = $type === 'Bedspace' ? 'per bed / day' : 'per day';
                return "• {$type}: {$range} {$unit}";
            })
            ->implode("\n");
    }

    private function liveAvailability(Collection $rooms): string
    {
        if ($rooms->isEmpty()) {
            return '';
        }

        return $rooms->groupBy('room_type')
            ->map(function ($group, $type) {
                $available = $group->where('status', 'Available')->count();
                $reserved  = $group->where('status', 'Reserved')->count();
                $occupied  = $group->where('status', 'Occupied')->count();
                return "• {$type}: {$available} available, {$reserved} reserved, {$occupied} occupied";
            })
            ->implode("\n");
    }
}
