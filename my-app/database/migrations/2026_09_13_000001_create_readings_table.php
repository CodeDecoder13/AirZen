<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table): void {
            $table->id();
            $table->float('temperature');
            $table->float('humidity');
            $table->float('co');
            $table->float('nitrogen');
            $table->float('pm25');
            $table->float('aqi');
            $table->string('status');
            $table->string('color');
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
