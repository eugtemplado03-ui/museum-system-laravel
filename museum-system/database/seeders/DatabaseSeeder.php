<?php

namespace Database\Seeders;

use App\Models\ArtifactLog;
use App\Models\CarouselSlide;
use App\Models\Category;
use App\Models\Exhibit;
use App\Models\Favorite;
use App\Models\GalleryItem;
use App\Models\MuseumEvent;
use App\Models\MuseumInfo;
use App\Models\Program;
use App\Models\Rating;
use App\Models\ScanEvent;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $jsonPath = database_path('data.json');
        $data = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

        // 1. Users
        $adminUser = env('SEED_ADMIN_USER', 'admin');
        $adminPass = env('SEED_ADMIN_PASS', 'museum-admin-2026');

        if (!empty($data['users'])) {
            foreach ($data['users'] as $u) {
                $pwd = ($u['username'] === $adminUser)
                    ? Hash::make($adminPass)
                    : ($u['passwordHash'] ?? Hash::make($adminPass));

                User::updateOrCreate(
                    ['username' => $u['username']],
                    [
                        'id' => $u['id'] ?? (string)Str::random(10),
                        'password' => $pwd,
                        'role' => $u['role'] ?? 'admin',
                    ]
                );
            }
        }

        if (!User::where('username', $adminUser)->exists()) {
            User::create([
                'id' => (string)Str::random(10),
                'username' => $adminUser,
                'password' => Hash::make($adminPass),
                'role' => 'admin',
            ]);
        }

        // 2. Categories
        if (!empty($data['categories'])) {
            foreach ($data['categories'] as $cat) {
                $name = is_array($cat) ? ($cat['name'] ?? null) : $cat;
                if ($name && trim($name)) {
                    Category::firstOrCreate(['name' => trim($name)]);
                }
            }
        }

        // 3. Exhibits
        if (!empty($data['exhibits'])) {
            foreach ($data['exhibits'] as $ex) {
                if (!empty($ex['category'])) {
                    Category::firstOrCreate(['name' => trim($ex['category'])]);
                }

                $imagePaths = $ex['imagePaths'] ?? [];
                if (empty($imagePaths) && !empty($ex['imagePath'])) {
                    $imagePaths = [$ex['imagePath']];
                }

                Exhibit::updateOrCreate(
                    ['code' => $ex['code']],
                    [
                        'id' => $ex['id'] ?? (string)Str::random(10),
                        'title' => $ex['title'],
                        'category' => $ex['category'] ?? null,
                        'origin' => $ex['origin'] ?? null,
                        'year' => $ex['year'] ?? null,
                        'location' => $ex['location'] ?? null,
                        'image_path' => $ex['imagePath'] ?? ($imagePaths[0] ?? null),
                        'image_paths' => array_values(array_unique(array_filter($imagePaths))),
                        'description' => $ex['description'] ?? null,
                        'additional_details' => $ex['additionalDetails'] ?? null,
                        'description_tl' => $ex['description_tl'] ?? null,
                        'description_cb' => $ex['description_cb'] ?? null,
                        'audio_path' => $ex['audioPath'] ?? null,
                        'video_path' => $ex['videoPath'] ?? null,
                        'floor' => (int)($ex['floor'] ?? 1),
                        'map_zone' => $ex['mapZone'] ?? null,
                        'pin_x' => isset($ex['pinX']) ? (float)$ex['pinX'] : null,
                        'pin_y' => isset($ex['pinY']) ? (float)$ex['pinY'] : null,
                        'directions' => $ex['directions'] ?? null,
                    ]
                );
            }
        }

        // 4. Programs
        if (!empty($data['programs'])) {
            foreach ($data['programs'] as $p) {
                Program::updateOrCreate(
                    ['id' => $p['id'] ?? (string)Str::random(10)],
                    [
                        'title' => $p['title'],
                        'description' => $p['description'] ?? null,
                        'schedule' => $p['schedule'] ?? null,
                        'image_path' => $p['imagePath'] ?? null,
                    ]
                );
            }
        }

        // 5. Events
        if (!empty($data['events'])) {
            foreach ($data['events'] as $ev) {
                MuseumEvent::updateOrCreate(
                    ['id' => $ev['id'] ?? (string)Str::random(10)],
                    [
                        'title' => $ev['title'],
                        'description' => $ev['description'] ?? null,
                        'date' => $ev['date'] ?? null,
                        'location' => $ev['location'] ?? null,
                        'image_path' => $ev['imagePath'] ?? null,
                    ]
                );
            }
        }

        // 6. Gallery
        if (!empty($data['gallery'])) {
            foreach ($data['gallery'] as $g) {
                GalleryItem::updateOrCreate(
                    ['id' => $g['id'] ?? (string)Str::random(10)],
                    [
                        'title' => $g['title'],
                        'category' => $g['category'] ?? null,
                        'image_path' => $g['imagePath'] ?? null,
                        'description' => $g['description'] ?? null,
                    ]
                );
            }
        }

        // 7. Museum Info
        if (!empty($data['museumInfo'])) {
            MuseumInfo::truncate();
            MuseumInfo::create(['data' => $data['museumInfo']]);
        } else {
            if (!MuseumInfo::exists()) {
                MuseumInfo::create([
                    'data' => [
                        'name' => 'Museo Sang Bata sa Negros',
                        'address' => 'Barangay Old Sagay, Sagay City, Negros Occidental',
                        'phone' => '+63 917 798 7420',
                        'email' => 'museosangbata@gmail.com',
                        'operatingHours' => 'Tuesday – Sunday: 8:00 AM – 5:00 PM',
                    ]
                ]);
            }
        }

        // 8. Carousel
        if (!empty($data['carousel'])) {
            foreach ($data['carousel'] as $slide) {
                CarouselSlide::updateOrCreate(
                    ['id' => $slide['id'] ?? (string)Str::random(10)],
                    [
                        'title' => $slide['title'] ?? null,
                        'subtitle' => $slide['subtitle'] ?? null,
                        'image_path' => $slide['imagePath'] ?? '',
                        'order' => (int)($slide['order'] ?? 0),
                        'is_active' => (bool)($slide['isActive'] ?? true),
                    ]
                );
            }
        }

        // 9. Visitors (if any)
        if (!empty($data['visitors'])) {
            foreach ($data['visitors'] as $v) {
                Visitor::updateOrCreate(
                    ['id' => $v['id'] ?? (string)Str::random(10)],
                    [
                        'pass_code' => $v['passCode'] ?? ('VP-' . strtoupper(Str::random(6))),
                        'visitor_name' => $v['visitorName'] ?? 'Visitor',
                        'address' => $v['address'] ?? 'Sagay City',
                        'sex' => $v['sex'] ?? 'Prefer not to say',
                        'age' => isset($v['age']) ? (int)$v['age'] : null,
                        'contact_number' => $v['contactNumber'] ?? null,
                        'email' => $v['email'] ?? null,
                        'group_name' => $v['groupName'] ?? null,
                        'group_type' => $v['groupType'] ?? 'Walk-in / Individual',
                        'pax' => (int)($v['pax'] ?? 1),
                        'purpose' => $v['purpose'] ?? 'General Visit',
                        'tour_guide' => $v['tourGuide'] ?? null,
                        'notes' => $v['notes'] ?? null,
                        'check_in_time' => isset($v['checkInTime']) ? $v['checkInTime'] : now(),
                        'check_out_time' => $v['checkOutTime'] ?? null,
                        'is_checked_in' => (bool)($v['isCheckedIn'] ?? true),
                    ]
                );
            }
        }
    }
}
