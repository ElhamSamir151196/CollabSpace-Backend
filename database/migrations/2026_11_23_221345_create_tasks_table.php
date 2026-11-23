<?php

use App\Enums\Priority;
use App\Enums\TaskStatus;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // The team responsible for the task (one of the project's teams).
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('status', 20)->default(TaskStatus::Pending->value);
            $table->string('priority', 20)->default(Priority::Medium->value);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            // Order of the card inside its Kanban column (0 = top).
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // The Kanban board reads tasks of one project, per column, in order.
            $table->index(['project_id', 'status', 'position']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
