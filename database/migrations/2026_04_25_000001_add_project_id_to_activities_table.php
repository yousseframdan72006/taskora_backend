<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->uuid('project_id')->nullable()->after('workspace_id');
            // We don't necessarily need a foreign key constraint, but if we do, we can add it.
            // But since activities might outlive a project if we change requirements, nullable without foreign is safer for now.
            // Or we can add it with cascadeOnDelete to automatically cleanup when a project is deleted.
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
