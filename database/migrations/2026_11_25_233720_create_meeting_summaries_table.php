<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "AI Generate Summary" tab of a meeting. One per meeting; generating
     * again replaces it.
     */
    public function up(): void
    {
        Schema::create('meeting_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('summary');
            $table->json('key_points');
            $table->json('decisions');
            $table->json('action_items');
            $table->json('risks');
            $table->json('next_steps');
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_summaries');
    }
};
