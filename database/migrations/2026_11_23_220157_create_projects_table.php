<?php

use App\Enums\Priority;
use App\Enums\ProjectStatus;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            // Used in URLs (/projects/alpha). Never changes after creation.
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            // e.g. "SaaS", "Mobile App".
            $table->string('type', 50)->nullable();
            $table->string('status', 20)->default(ProjectStatus::Pending->value)->index();
            $table->string('priority', 20)->default(Priority::Medium->value)->index();
            $table->date('start_date');
            $table->date('deadline')->index();
            $table->timestamp('completed_at')->nullable();
            // The Project Manager responsible for the project.
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
