<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember which reminders were already sent, so the scheduled
     * commands never notify twice for the same meeting / due date.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_at');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('due_reminder_sent_at')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('due_reminder_sent_at');
        });
    }
};
