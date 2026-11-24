<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transcript lines, entered by the person who created the meeting:
     * "00:40  Ahmed Hassan  Morning. I went through the new homepage design ..."
     */
    public function up(): void
    {
        Schema::create('meeting_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            // The speaker when they are a user of the app; speaker_name is always filled.
            $table->foreignId('speaker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('speaker_name', 150);
            $table->unsignedInteger('offset_seconds');
            $table->text('content');
            $table->string('language', 10)->default('en');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['meeting_id', 'offset_seconds', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_transcripts');
    }
};
