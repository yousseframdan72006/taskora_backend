<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token');
            $table->string('platform')->default('android'); // android | ios | web
            $table->timestamps();

            // Each device token is unique globally
            $table->unique('device_token');
            // Each user can have multiple devices but not the same token twice
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
