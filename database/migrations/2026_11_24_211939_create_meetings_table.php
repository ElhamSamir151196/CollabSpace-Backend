<?php

use App\Enums\MeetingStatus;
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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            // "Add Note" in the form: the goal of the meeting.
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            // External link (Zoom, Google Meet, Teams ...). There is no video inside the app.
            $table->string('meeting_link', 500)->nullable();
            // Image shown on the meeting page; clicking it opens the link.
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default(MeetingStatus::Scheduled->value);
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'start_at']);
            $table->index(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
