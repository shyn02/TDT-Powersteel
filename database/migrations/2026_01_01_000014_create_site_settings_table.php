<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton table — application enforces a single row (id=1) via
        // SiteSettings::current() in the model. Mirrors Django's get_solo() pattern.
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // General
            $table->string('site_name', 150)->default('TDT Powersteel');
            $table->string('support_email')->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->string('company_address', 255)->nullable();

            // Security
            $table->unsignedInteger('session_timeout_minutes')->default(60);
            $table->unsignedInteger('max_login_attempts')->default(5);
            $table->unsignedInteger('lockout_minutes')->default(15);
            $table->boolean('require_strong_passwords')->default(true);

            // Notifications
            $table->boolean('notify_new_quote')->default(true);
            $table->boolean('notify_new_referral')->default(true);
            $table->boolean('notify_new_chat')->default(true);
            $table->string('notification_email')->nullable();

            // Regional
            $table->string('timezone_name', 50)->default('Asia/Manila');
            $table->string('currency', 10)->default('PHP');
            $table->string('date_format', 30)->default('F j, Y');

            // Tools / maintenance
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')
                ->default('System is currently under maintenance. Please check back later.');

            $table->timestamp('updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
