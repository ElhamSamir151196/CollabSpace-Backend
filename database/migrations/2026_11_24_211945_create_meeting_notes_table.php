<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Note" and "Comments" tabs of the meeting page.
     */
    public function up(): void
    {
        Schema::create('meeting_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['meeting_id', 'type', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_notes');
    }
};
