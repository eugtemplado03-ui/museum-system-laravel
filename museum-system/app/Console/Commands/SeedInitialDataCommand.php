<?php

namespace App\Console\Commands;

use App\Models\Exhibit;
use Illuminate\Console\Command;

class SeedInitialDataCommand extends Command
{
    protected $signature = 'museum:seed-if-empty {--force : Confirm seeding in production}';

    protected $description = 'Seed initial museum data only when the exhibits table is empty';

    public function handle(): int
    {
        if (Exhibit::query()->exists()) {
            $this->info('Museum data already exists; skipping initial seeding.');

            return self::SUCCESS;
        }

        $this->info('No exhibits found; seeding initial museum data.');

        return $this->call('db:seed', ['--force' => $this->option('force')]);
    }
}
