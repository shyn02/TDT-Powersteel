<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->date('date_added');
            $table->string('contractor', 200);
            $table->text('project_name');
            $table->decimal('value', 16, 2)->default(0);
            $table->enum('status', ['for_bidding', 'won', 'lost', 'ongoing'])->default('for_bidding');
            $table->boolean('is_priority')->default(false);
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
