<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\MuseumEvent;
use App\Models\MuseumInfo;
use App\Models\Program;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected function buildMuseumContext(): string
    {
        $infoRecord = MuseumInfo::first();
        $info = $infoRecord ? $infoRecord->data : [];
        $exhibits = Exhibit::all();
        $programs = Program::all();
        $events = MuseumEvent::all();

        $lines = ["You are the friendly, knowledgeable digital guide of Museo Sang Bata sa Negros (Children's Museum) in Sagay City, Negros Occidental, Philippines."];
        $lines[] = 'You can understand and respond fluently in English, Hiligaynon (Ilonggo), Cebuano (Bisaya), and Tagalog (Filipino).';
        $lines[] = "\n--- MUSEUM GENERAL INFORMATION ---";
        $lines[] = 'Name: '.($info['name'] ?? 'Museo Sang Bata sa Negros');
        $lines[] = 'Location: '.($info['address'] ?? 'Barangay Old Sagay, Sagay City, Negros Occidental');
        $lines[] = 'Contact: '.($info['phone'] ?? '+63 917 798 7420');

        $lines[] = "\n--- EXHIBITS ON DISPLAY ---";
        foreach ($exhibits as $exhibit) {
            $lines[] = "- [{$exhibit->code}] {$exhibit->title} (Category: {$exhibit->category}, Floor: Level {$exhibit->floor}): {$exhibit->description}";
            if ($exhibit->directions) {
                $lines[] = "  Directions: {$exhibit->directions}";
            }
        }

        $lines[] = "\n--- EDUCATIONAL PROGRAMS ---";
        foreach ($programs as $program) {
            $lines[] = "- {$program->title}: {$program->description} (Schedule: {$program->schedule})";
        }

        $lines[] = "\n--- UPCOMING EVENTS ---";
        foreach ($events as $event) {
            $lines[] = "- {$event->title}: {$event->description} (Date: {$event->date}, Location: {$event->location})";
        }

        return implode("\n", $lines);
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'language' => ['nullable', 'string', 'max:16'],
            'history' => ['nullable', 'array'],
        ]);
        $message = trim($validated['message']);
        $language = $validated['language'] ?? 'en';

        if ($message === '') {
            return response()->json(['error' => 'Message is required.'], 400);
        }

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['error' => 'Groq chatbot is not configured. Set GROQ_API_KEY.'], 503);
        }

        $systemContext = $this->buildMuseumContext();
        $systemPrompt = $systemContext."\n\nPlease respond warmly, concisely, and accurately in the visitor's preferred language ({$language}). If asked where an exhibit is located, provide clear directions.";
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach (array_slice($validated['history'] ?? [], -6) as $historyMessage) {
            if (
                is_array($historyMessage)
                && isset($historyMessage['role'], $historyMessage['content'])
                && is_string($historyMessage['content'])
            ) {
                $messages[] = [
                    'role' => $historyMessage['role'] === 'user' ? 'user' : 'assistant',
                    'content' => mb_substr($historyMessage['content'], 0, 1000),
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(25)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.chat_model', 'openai/gpt-oss-120b'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 800,
                ]);

            if (! $response->successful()) {
                Log::warning('Groq chatbot request failed.', ['status' => $response->status()]);

                return response()->json(['error' => 'Groq chatbot request failed. Please try again shortly.'], 502);
            }

            $reply = trim((string) $response->json('choices.0.message.content'));
            if ($reply === '') {
                Log::warning('Groq chatbot returned an empty reply.');

                return response()->json(['error' => 'Groq chatbot returned an empty reply. Please try again.'], 502);
            }

            return response()->json(['reply' => $reply, 'language' => $language]);
        } catch (ConnectionException $exception) {
            Log::warning('Groq chatbot connection failed.', ['message' => $exception->getMessage()]);

            return response()->json(['error' => 'Unable to connect to the Groq chatbot. Please try again shortly.'], 502);
        }
    }
}
