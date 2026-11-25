<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Messages of an AI conversation. Assistant messages keep which provider /
     * model answered and the token usage (AI activity log, costs).
     */
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();

            $table->index(['ai_conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
