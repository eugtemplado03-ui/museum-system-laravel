<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PDO;

class MuseumSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'museum:setup {--sqlite : Force SQLite database driver instead of MySQL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the Museum database and seed all exhibits, events, and admin account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=================================================");
        $this->info(" Museo Sang Bata sa Negros - System Setup");
        $this->info("=================================================");

        $useSqlite = $this->option('sqlite');
        $dbConnection = $useSqlite ? 'sqlite' : env('DB_CONNECTION', 'mysql');

        if ($dbConnection === 'mysql') {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $dbName = env('DB_DATABASE', 'museum_system');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', '');

            $this->info("Connecting to MySQL at {$host}:{$port}...");

            try {
                $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $this->info(" Database `{$dbName}` verified/created successfully.");
            } catch (\Exception $e) {
                $this->error(" Could not connect to MySQL server at {$host}:{$port}.");
                $this->warn("Detail: " . $e->getMessage());
                $this->line("");
                $this->line("Troubleshooting:");
                $this->line("1. Open the XAMPP Control Panel and click [Start] next to MySQL.");
                $this->line("2. Or run setup with SQLite: php artisan museum:setup --sqlite");
                return 1;
            }
        } else {
            $sqliteFile = database_path('database.sqlite');
            if (!file_exists($sqliteFile)) {
                touch($sqliteFile);
            }
            $this->info(" SQLite database initialized at {$sqliteFile}.");
        }

        $this->info("Running migrations and seeding initial data on [{$dbConnection}]...");
        $exitCode = Artisan::call('migrate:fresh', [
            '--database' => $dbConnection,
            '--seed' => true,
            '--force' => true,
        ], $this->output);

        if ($exitCode === 0) {
            $this->line("");
            $this->info("=================================================");
            $this->info(" Setup completed successfully!");
            $this->info(" Admin Account:");
            $this->line("   Username: " . env('SEED_ADMIN_USER', 'admin'));
            $this->line("   Password: " . env('SEED_ADMIN_PASS', 'museum-admin-2026'));
            $this->info(" Start Server:");
            $this->line("   php artisan serve");
            $this->info("=================================================");
            return 0;
        }

        $this->error("Migration or seeding encountered an error.");
        return $exitCode;
    }
}
