<?php

use App\Enums\AvailabilityStatus;
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
        Schema::table('users', function (Blueprint $table) {
            // Google-only accounts have no password.
            $table->string('password')->nullable()->change();

            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('phone', 30)->nullable()->after('avatar_path');
            $table->string('job_title')->nullable()->after('phone');
            $table->unsignedTinyInteger('years_of_experience')->nullable()->after('job_title');
            $table->string('availability_status', 20)
                ->default(AvailabilityStatus::Available->value)
                ->after('years_of_experience');
            $table->boolean('is_active')->default(true)->index()->after('availability_status');
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'google_id',
                'avatar_path',
                'phone',
                'job_title',
                'years_of_experience',
                'availability_status',
                'is_active',
                'last_seen_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
