<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the hottest read paths found while reviewing the queries:
 *  - notifications: unread badge / list per user (polled by the frontend);
 *  - tasks.completed_at: dashboard trends and every report (date ranges);
 *  - activity_log.created_at: Admin > Activity log (newest first, date filters).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('completed_at');
        });

        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
        });

        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
