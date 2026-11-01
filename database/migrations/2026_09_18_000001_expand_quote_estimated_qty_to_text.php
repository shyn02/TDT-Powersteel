<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_requests', function (Blueprint $table): void {
            $table->text('estimated_qty')->change();
        });
    }

    public function down(): void
    {
        $hasValuesThatWouldBeTruncated = false;

        DB::table('quote_requests')
            ->select(['id', 'estimated_qty'])
            ->orderBy('id')
            ->chunkById(1000, function ($quotes) use (&$hasValuesThatWouldBeTruncated) {
                foreach ($quotes as $quote) {
                    if (Str::length((string) $quote->estimated_qty) > 150) {
                        $hasValuesThatWouldBeTruncated = true;

                        return false;
                    }
                }
            });

        if ($hasValuesThatWouldBeTruncated) {
            throw new RuntimeException(
                'Cannot restore quote_requests.estimated_qty to varchar(150): one or more values exceed 150 characters.'
            );
        }

        Schema::table('quote_requests', function (Blueprint $table): void {
            $table->string('estimated_qty', 150)->change();
        });
    }
};
