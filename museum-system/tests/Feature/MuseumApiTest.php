<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'museum-admin-2026',
        ]);
        $this->withHeader('Authorization', 'Bearer '.$login->json('token'))
            ->getJson('/api/visitors?limit=100')
            ->assertOk()
            ->assertJsonPath('visitors.0.visitDate', now('Asia/Manila')->toDateString())
            ->assertJsonPath('visitors.0.visitTime', now('Asia/Manila')->format('H:i'));
    }

    public function test_admin_can_create_and_delete_a_walkin_visitor(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'museum-admin-2026',
        ]);
        $this->withHeader('Authorization', 'Bearer '.$login->json('token'));

        $response = $this->postJson('/api/visitors', [
            'visitorName' => 'Juan Dela Cruz',
            'address' => 'Sagay City',
            'sex' => 'Male',
            'age' => 32,
            'groupName' => 'Family',
            'groupType' => 'Family',
            'pax' => 4,
            'purpose' => 'General Visit',
            'visitDate' => '2026-06-10',
            'visitTime' => '09:30',
        ]);

        $response->assertCreated()
            ->assertJsonPath('visitor.visitDate', '2026-06-10')
            ->assertJsonPath('visitor.visitTime', '09:30');

        $id = $response->json('visitor.id');
        $this->assertDatabaseHas('visitors', ['id' => $id, 'visitor_name' => 'Juan Dela Cruz']);

        $this->deleteJson("/api/visitors/{$id}")
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('visitors', ['id' => $id]);
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

    public function test_exhibit_activity_tracking_feeds_analytics_contract(): void
    {
        $exhibit = Exhibit::first();
        $this->postJson("/api/exhibits/{$exhibit->code}/track", ['source' => 'scan'])->assertOk();
        $this->postJson("/api/exhibits/{$exhibit->code}/track", ['source' => 'view'])->assertOk();

        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'museum-admin-2026',
        ]);
        $response = $this->withHeader('Authorization', 'Bearer '.$login->json('token'))
            ->getJson('/api/admin/analytics')
            ->assertOk();

        $response->assertJsonPath('totals.allTime', 2)
            ->assertJsonPath('totals.scans', 1)
            ->assertJsonPath('totals.views', 1)
            ->assertJsonPath('byExhibit.0.scans', 1)
            ->assertJsonPath('byExhibit.0.views', 1)
            ->assertJsonPath('byExhibit.0.total', 2)
            ->assertJsonPath('daily.29.date', now('Asia/Manila')->toDateString())
            ->assertJsonPath('daily.29.total', 2);
        $this->assertIsString($response->json('recent.0.at'));
    }

    public function test_chat_assistant_returns_reply(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The museum opens at 8 AM.']],
                ],
            ]),
        ]);

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
