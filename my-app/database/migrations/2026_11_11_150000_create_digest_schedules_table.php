<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row settings table: one global digest schedule.
        Schema::create('digest_schedules', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('mode', 10)->default('daily'); // 'daily' | 'interval'
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->string('send_at', 5)->default('07:00'); // HH:MM in app.digest_timezone
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_schedules');
    }
};
