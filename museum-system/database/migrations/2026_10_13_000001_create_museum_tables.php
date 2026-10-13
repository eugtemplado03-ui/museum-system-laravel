<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('exhibits', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('origin')->nullable();
            $table->string('year')->nullable();
            $table->string('location')->nullable();
            $table->text('image_path')->nullable();
            $table->longText('description')->nullable();
            $table->longText('additional_details')->nullable();
            $table->longText('description_tl')->nullable();
            $table->longText('description_cb')->nullable();
            $table->json('image_paths')->nullable();
            $table->json('optimized_image_paths')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('video_path')->nullable();
            $table->integer('floor')->default(1);
            $table->string('map_zone')->nullable();
            $table->double('pin_x')->nullable();
            $table->double('pin_y')->nullable();
            $table->text('directions')->nullable();
            $table->timestamps();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);
            $table->string('visitor_name')->nullable();
            $table->string('exhibit_id', 64);
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);
            $table->string('exhibit_id', 64);
            $table->timestamps();
        });

        Schema::create('scan_events', function (Blueprint $table) {
            $table->id();
            $table->string('exhibit_id', 64)->nullable();
            $table->string('visitor_id', 64)->nullable();
            $table->string('source')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('schedule')->nullable();
            $table->text('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('museum_events', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('date')->nullable();
            $table->string('location')->nullable();
            $table->text('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('image_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('visitors', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('pass_code', 64)->unique();
            $table->string('visitor_name');
            $table->string('address');
            $table->string('sex');
            $table->integer('age')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('group_name')->nullable();
            $table->string('group_type')->default('Walk-in / Individual');
            $table->integer('pax')->default(1);
            $table->string('purpose')->default('General Visit');
            $table->string('tour_guide')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->boolean('is_checked_in')->default(true);
            $table->timestamps();
        });

        Schema::create('artifact_logs', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('visitor_id', 64);
            $table->string('exhibit_id', 64);
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('museum_info', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('carousel_slides', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('image_path');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carousel_slides');
        Schema::dropIfExists('museum_info');
        Schema::dropIfExists('artifact_logs');
        Schema::dropIfExists('visitors');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('museum_events');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('scan_events');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('exhibits');
        Schema::dropIfExists('categories');
    }
};
