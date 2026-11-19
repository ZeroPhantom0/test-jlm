<?php
// app/Http/Controllers/ChatbotController.php

namespace App\Http\Controllers;

use App\Models\ChatbotCategory;
use App\Models\ChatbotFaq;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public endpoints for the landing-page FAQ chatbot.
 */
class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $bot) {}

    /** Greeting, system lines and the category menu. */
    public function bootstrap(): JsonResponse
    {
        return response()->json($this->bot->bootstrap());
    }

    /** Visitor typed a free-text message. */
    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:300'],
        ]);

        return response()->json($this->bot->ask($data['message']));
    }

    /** Visitor tapped a category chip. */
    public function category(ChatbotCategory $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        return response()->json($this->bot->openCategory($category));
    }

    /** Visitor tapped a suggested question. */
    public function faq(ChatbotFaq $faq): JsonResponse
    {
        abort_unless($faq->is_active, 404);

        return response()->json($this->bot->answerFaq($faq));
    }
}
