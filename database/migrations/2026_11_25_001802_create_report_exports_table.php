<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PDF reports generated with "Submit a detailed report",
     * kept so they can be downloaded again.
     */
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            // Who generated it.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            // For user reports: whose report it is.
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->string('disk', 30);
            $table->string('path', 500);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
