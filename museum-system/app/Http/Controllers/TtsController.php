<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TtsController extends Controller
{
    public function generate(Request $request)
    {
        return $this->speak($request);
    }

    public function speak(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:1500'],
        ]);
        $text = trim($validated['text']);

        if ($text === '') {
            return response()->json(['error' => 'Text is required for TTS synthesis.'], 400);
        }

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['error' => 'Groq TTS is not configured. Set GROQ_API_KEY and restart the server.'], 503);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(25)->post('https://api.groq.com/openai/v1/audio/speech', [
                'model' => 'canopylabs/orpheus-v1-english',
                'voice' => 'autumn',
                'response_format' => 'wav',
                'input' => $text,
            ]);

            if ($response->successful()) {
                return response($response->body(), 200, [
                    'Content-Type' => 'audio/wav',
                    'Content-Disposition' => 'inline; filename="narration.wav"',
                ]);
            }

            Log::warning('Groq TTS request failed.', ['status' => $response->status()]);

            return response()->json(['error' => 'Groq TTS synthesis failed.'], 502);
        } catch (ConnectionException $exception) {
            Log::warning('Groq TTS connection failed.', ['message' => $exception->getMessage()]);

            return response()->json(['error' => 'Unable to connect to Groq TTS.'], 502);
        }
    }

    public function translate(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:10000'],
            'targetLang' => ['required', 'string', 'in:en,original,tl,cb,hil'],
        ]);
        $text = trim($validated['text']);
        $targetLang = $validated['targetLang'];

        if ($targetLang === 'en' || $targetLang === 'original') {
            return response()->json(['translatedText' => $text]);
        }

        $languageNames = [
            'tl' => 'Filipino (Tagalog)',
            'cb' => 'Cebuano (Bisaya)',
            'hil' => 'Hiligaynon (Ilonggo)',
        ];
        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['error' => 'Groq translation is not configured. Set GROQ_API_KEY and restart the server.'], 503);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'openai/gpt-oss-120b',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Translate the user's museum exhibit text into {$languageNames[$targetLang]}. Keep proper names unchanged and return only the translation.",
                        ],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'temperature' => 1,
                    'max_tokens' => 2048,
                    'top_p' => 1,
                    'reasoning_effort' => 'medium',
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Groq translation request failed.', ['status' => $response->status()]);

                return response()->json(['error' => 'Groq translation failed.'], 502);
            }

            $translatedText = trim((string) $response->json('choices.0.message.content'));
            if ($translatedText === '') {
                Log::warning('Groq returned an empty translation.');

                return response()->json(['error' => 'Groq returned an empty translation.'], 502);
            }

            return response()->json(['translatedText' => $translatedText]);
        } catch (ConnectionException $exception) {
            Log::warning('Groq translation connection failed.', ['message' => $exception->getMessage()]);

            return response()->json(['error' => 'Unable to connect to Groq translation.'], 502);
        }
    }
}
