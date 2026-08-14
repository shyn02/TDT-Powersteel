<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('full_name', 150);
            $table->string('company_name', 150)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('how_heard', 100)->nullable();
            $table->string('estimated_qty', 150);
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');
            $table->boolean('is_seen')->default(false); // sidebar badge tracking, separate from status
            $table->enum('source', ['home', 'product'])->default('home');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
