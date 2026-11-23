<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uploaded files. "attachable" is what the file belongs to
     * (a project or a task now; chat messages and meetings later).
     * project_id is copied on every file so the project "Files" tab
     * can list project + task files with one simple query.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('disk', 30);
            $table->string('path', 500);
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
