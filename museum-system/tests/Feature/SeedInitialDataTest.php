<?php

namespace Tests\Feature;

use App\Models\Exhibit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedInitialDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_empty_museum_tables_only_once(): void
    {
        $this->artisan('museum:seed-if-empty', ['--force' => true])
            ->assertSuccessful();

        $exhibit = Exhibit::query()->firstOrFail();
        $exhibit->update(['title' => 'Keep this curated title']);

        $this->artisan('museum:seed-if-empty', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('exhibits', [
            'id' => $exhibit->id,
            'title' => 'Keep this curated title',
        ]);
    }
}
