<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\MuseumEvent;
use App\Models\MuseumInfo;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    protected function buildMuseumContext()
    {
        $infoRecord = MuseumInfo::first();
        $info = $infoRecord ? $infoRecord->data : [];
        $exhibits = Exhibit::all();
        $programs = Program::all();
        $events = MuseumEvent::all();

        $lines = ["You are the friendly, knowledgeable digital guide of Museo Sang Bata sa Negros (Children's Museum) in Sagay City, Negros Occidental, Philippines."];
        $lines[] = "You can understand and respond fluently in English, Hiligaynon (Ilonggo), Cebuano (Bisaya), and Tagalog (Filipino).";
        $lines[] = "\n--- MUSEUM GENERAL INFORMATION ---";
        $lines[] = "Name: " . ($info['name'] ?? 'Museo Sang Bata sa Negros');
        $lines[] = "Location: " . ($info['address'] ?? 'Barangay Old Sagay, Sagay City, Negros Occidental');
        $lines[] = "Contact: " . ($info['phone'] ?? '+63 917 798 7420');

        $lines[] = "\n--- EXHIBITS ON DISPLAY ---";
        foreach ($exhibits as $ex) {
            $lines[] = "- [{$ex->code}] {$ex->title} (Category: {$ex->category}, Floor: Level {$ex->floor}): {$ex->description}";
            if ($ex->directions) {
                $lines[] = "  Directions: {$ex->directions}";
            }
        }

        $lines[] = "\n--- EDUCATIONAL PROGRAMS ---";
        foreach ($programs as $prg) {
            $lines[] = "- {$prg->title}: {$prg->description} (Schedule: {$prg->schedule})";
        }

        $lines[] = "\n--- UPCOMING EVENTS ---";
        foreach ($events as $ev) {
            $lines[] = "- {$ev->title}: {$ev->description} (Date: {$ev->date}, Location: {$ev->location})";
        }

        return implode("\n", $lines);
    }

    public function chat(Request $request)
    {
        $message = trim((string)$request->input('message', ''));
        $language = $request->input('language', 'en');
        $history = $request->input('history') ?: [];

        if (!$message) {
            return response()->json(['error' => 'Message is required.'], 400);
        }

        $apiKey = env('OPENROUTER_API_KEY');
        if (!$apiKey) {
            $fallback = match ($language) {
                'hil' => "Maayong adlaw! Ako ang digital tour guide sang Museo Sang Bata sa Negros. Sa subong, ang AI assistant nagahulat sang API key configuration sa .env file. Apang makalibot ka gihapon sa amon mga exhibits!",
                'ceb' => "Maayong adlaw! Ako ang digital tour guide sa Museo Sang Bata sa Negros. Sa karon, ang AI assistant naghulat pa sa API key configuration. Apan pwede ka gihapon makasuroy sa among mga exhibits!",
                'tl'  => "Magandang araw! Ako ang digital guide ng Museo Sang Bata sa Negros. Sa ngayon, naghihintay pa ang AI assistant ng API key sa .env file. Maaari mo pa ring bisitahin ang ating mga exhibits!",
                default => "Welcome to Museo Sang Bata sa Negros! I am your museum assistant. Currently, the AI backend requires an OPENROUTER_API_KEY in the .env configuration, but you can explore all our interactive exhibits directly!",
            };
            return response()->json(['reply' => $fallback, 'language' => $language]);
        }

        $systemContext = $this->buildMuseumContext();
        $systemPrompt = $systemContext . "\n\nPlease respond warmly, concisely, and accurately in the visitor's preferred language ({$language}). If asked where an exhibit is located, provide clear directions.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if (is_array($history)) {
            foreach (array_slice($history, -6) as $msg) {
                if (isset($msg['role'], $msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                        'content' => mb_substr($msg['content'], 0, 1000),
                    ];
                }
            }
        }

        $messages[] = ['role' => 'user', 'content' => mb_substr($message, 0, 1000)];

        try {
            $model = env('OPENROUTER_MODEL', 'openai/gpt-4o');
            $siteUrl = env('SITE_URL', 'http://localhost:3000');
            $siteName = env('SITE_NAME', 'Museo Sang Bata sa Negros');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'HTTP-Referer' => $siteUrl,
                'X-Title' => $siteName,
                'Content-Type' => 'application/json',
            ])->timeout(25)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';
                return response()->json(['reply' => $reply, 'language' => $language]);
            } else {
                return response()->json([
                    'reply' => "I'm having trouble connecting right now. Please try again in a moment.",
                    'error' => $response->body(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'reply' => "The museum assistant encountered a connection issue. Please try again shortly.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
