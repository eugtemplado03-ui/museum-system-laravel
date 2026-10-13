<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('serveMuseumHtml')) {
    function serveMuseumHtml($filename) {
        $path = public_path($filename);
        if (!file_exists($path)) {
            return response("File {$filename} not found", 404);
        }
        return response()->file($path, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}

Route::get('/', fn() => serveMuseumHtml('index.html'));
Route::get('/login', fn() => serveMuseumHtml('index.html'));
Route::get('/checkin', fn() => serveMuseumHtml('index.html'));

Route::get('/exhibits', fn() => serveMuseumHtml('exhibits.html'));
Route::get('/exhibit', fn() => serveMuseumHtml('exhibit.html'));
Route::get('/admin', fn() => serveMuseumHtml('admin.html'));
Route::get('/dashboard', fn() => serveMuseumHtml('dashboard.html'));
Route::get('/map', fn() => serveMuseumHtml('map.html'));
Route::get('/events', fn() => serveMuseumHtml('events.html'));
Route::get('/event', fn() => serveMuseumHtml('event.html'));
Route::get('/programs', fn() => serveMuseumHtml('programs.html'));
Route::get('/program', fn() => serveMuseumHtml('program.html'));
Route::get('/gallery', fn() => serveMuseumHtml('gallery.html'));
Route::get('/gallery-item', fn() => serveMuseumHtml('gallery-item.html'));
Route::get('/about', fn() => serveMuseumHtml('about.html'));
Route::get('/contact', fn() => serveMuseumHtml('contact.html'));
Route::get('/donate', fn() => serveMuseumHtml('donate.html'));
