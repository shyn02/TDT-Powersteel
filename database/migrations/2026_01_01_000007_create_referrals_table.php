<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referrer_name', 200);
            $table->string('referrer_company', 200)->nullable();
            $table->string('referrer_phone', 50);
            $table->string('referrer_email');
            $table->string('contact_person', 200);
            $table->string('referred_company', 200);
            $table->string('project_type', 100);
            $table->string('project_scale', 100);
            $table->string('region', 150);
            $table->text('remarks')->nullable();
            $table->enum('status', ['new', 'contacted', 'rewarded'])->default('new');
            $table->boolean('is_seen')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
