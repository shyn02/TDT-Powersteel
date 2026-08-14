<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->string('client_name', 150)->default('Website Visitor');
            $table->string('page', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_message_at')->useCurrent();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['unassigned', 'active', 'closed'])->default('unassigned');
            $table->boolean('is_priority')->default(false); // true if Admin hand-assigned, bypassing queue

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
