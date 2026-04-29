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
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('task_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('action'); // status_changed, assigned, commented, updated
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('role')->nullable(); // the role of the user who made the action
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
