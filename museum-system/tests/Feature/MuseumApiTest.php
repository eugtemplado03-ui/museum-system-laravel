<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuseumApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_health_check_returns_ok(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_exhibits_endpoint_returns_data(): void
    {
        $response = $this->get('/api/exhibits');
        $response->assertStatus(200);
        $response->assertJsonStructure(['exhibits']);
    }

    public function test_categories_endpoint_returns_categories(): void
    {
        $response = $this->get('/api/exhibits/categories');
        $response->assertStatus(200);
        $response->assertJsonStructure(['categories']);
    }

    public function test_visitor_checkin_and_verification(): void
    {
        $payload = [
            'visitorName' => 'Maria Santos',
            'address' => 'Sagay City',
            'sex' => 'Female',
            'age' => 28,
            'email' => 'maria@example.com',
            'contactNumber' => '09123456789',
        ];

        $response = $this->postJson('/api/visitors/checkin', $payload);
        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'visitor', 'qrCode']);

        $passCode = $response->json('visitor.passCode');

        // Verify pass
        $verify = $this->getJson("/api/visitors/verify/{$passCode}");
        $verify->assertStatus(200);
        $verify->assertJson(['valid' => true]);
    }

    public function test_auth_login_with_seeded_admin(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'museum-admin-2026',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_exhibit_rating_submission(): void
    {
        $exhibit = Exhibit::first();

        $response = $this->postJson("/api/exhibits/{$exhibit->code}/ratings", [
            'visitorId' => 'test-visitor-123',
            'visitorName' => 'Juan',
            'rating' => 5,
            'comment' => 'Spectacular exhibit!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['rating', 'summary']);
    }

    public function test_admin_analytics_requires_auth(): void
    {
        // Unauthenticated access
        $unauth = $this->getJson('/api/admin/analytics');
        $unauth->assertStatus(401);

        // Authenticate
        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'museum-admin-2026',
        ]);
        $token = $login->json('token');

        // Authenticated access
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/analytics');

        $response->assertStatus(200);
        $response->assertJsonStructure(['totals', 'byExhibit', 'recent', 'daily']);
    }

    public function test_chat_assistant_returns_reply(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => 'What are your museum hours?',
            'language' => 'en',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply', 'language']);
    }

    public function test_programs_and_events_endpoints(): void
    {
        $programs = $this->getJson('/api/programs');
        $programs->assertStatus(200);
        $programs->assertJsonStructure(['programs']);

        $events = $this->getJson('/api/events');
        $events->assertStatus(200);
        $events->assertJsonStructure(['events']);
    }
}
