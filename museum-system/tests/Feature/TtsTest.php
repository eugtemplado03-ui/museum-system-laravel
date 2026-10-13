<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TtsTest extends TestCase
{
    public function test_speak_returns_groq_audio(): void
    {
        config(['services.groq.key' => 'test-groq-key']);
        Http::fake([
            'https://api.groq.com/openai/v1/audio/speech' => Http::response('wav-audio-data', 200),
        ]);

        $response = $this->postJson('/api/tts/speak', ['text' => 'Welcome to the museum.']);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'audio/wav');
        $this->assertSame('wav-audio-data', $response->getContent());

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.groq.com/openai/v1/audio/speech'
            && $request->hasHeader('Authorization', 'Bearer test-groq-key')
            && $request['model'] === 'canopylabs/orpheus-v1-english'
            && $request['response_format'] === 'wav'
            && $request['input'] === 'Welcome to the museum.'
        );
    }

    public function test_speak_reports_missing_groq_key(): void
    {
        config(['services.groq.key' => null]);

        $this->postJson('/api/tts/speak', ['text' => 'Welcome to the museum.'])
            ->assertStatus(503)
            ->assertJsonPath('error', 'Groq TTS is not configured. Set GROQ_API_KEY and restart the server.');
    }

    public function test_translate_returns_groq_translation(): void
    {
        config(['services.groq.key' => 'test-groq-key']);
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Maligayang pagdating sa museo.']],
                ],
            ]),
        ]);

        $this->postJson('/api/tts/translate', [
            'text' => 'Welcome to the museum.',
            'targetLang' => 'tl',
        ])->assertOk()
            ->assertJsonPath('translatedText', 'Maligayang pagdating sa museo.');
    }
}
