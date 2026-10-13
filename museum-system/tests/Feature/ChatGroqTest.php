<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatGroqTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_sends_messages_to_groq(): void
    {
        config([
            'services.groq.key' => 'test-groq-key',
            'services.groq.chat_model' => 'openai/gpt-oss-120b',
        ]);
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The museum opens at 8 AM.']],
                ],
            ]),
        ]);

        $this->postJson('/api/chat', [
            'message' => 'What time does the museum open?',
            'language' => 'en',
            'history' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Welcome!'],
            ],
        ])->assertOk()
            ->assertJsonPath('reply', 'The museum opens at 8 AM.')
            ->assertJsonPath('language', 'en');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-groq-key')
            && $request['model'] === 'openai/gpt-oss-120b'
            && $request['max_tokens'] === 800
            && $request['messages'][1]['content'] === 'Hello'
            && $request['messages'][2]['content'] === 'Welcome!'
            && $request['messages'][3]['content'] === 'What time does the museum open?'
        );
    }

    public function test_chat_requires_a_groq_api_key(): void
    {
        config(['services.groq.key' => null]);

        $this->postJson('/api/chat', ['message' => 'Hello'])
            ->assertStatus(503)
            ->assertJsonPath('error', 'Groq chatbot is not configured. Set GROQ_API_KEY.');
    }

    public function test_chat_reports_provider_failure_without_exposing_provider_details(): void
    {
        config(['services.groq.key' => 'test-groq-key']);
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'private provider detail'],
            ], 402),
        ]);

        $this->postJson('/api/chat', ['message' => 'Hello'])
            ->assertStatus(502)
            ->assertJsonPath('error', 'Groq chatbot request failed. Please try again shortly.')
            ->assertDontSee('private provider detail');
    }
}
