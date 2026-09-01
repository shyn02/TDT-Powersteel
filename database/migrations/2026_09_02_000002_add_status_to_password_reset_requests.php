<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER ENUM, so we need to recreate the table
        // For other DBs, we would alter the column, but for simplicity we just update the check constraint via raw
        // First, update existing data to use new statuses if needed (none yet)
        // Then recreate the table with new enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: drop and recreate with new check
            $sql = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='password_reset_requests'")[0]->sql ?? '';
            // If already has new enum, skip
            if (str_contains($sql, "'approved'")) {
                return;
            }
            Schema::table('password_reset_requests', function ($table) {
                // No direct way to alter enum in SQLite, so we will just not enforce via DB for now
                // We'll drop the old table and recreate via raw if needed, but easier: just allow any status by removing check
                // For SQLite, we can just not worry — the check is in the table creation, but we can update it via raw
            });
            // Workaround: SQLite check constraint is not strictly enforced for new values if we use raw update
            // Instead, we will just update the table to allow any status by recreating
            DB::statement('DROP TABLE IF EXISTS password_reset_requests_old');
            DB::statement('ALTER TABLE password_reset_requests RENAME TO password_reset_requests_old');
            DB::statement("CREATE TABLE password_reset_requests (id integer primary key autoincrement not null, email varchar not null, user_id integer, status varchar check (\"status\" in ('pending', 'approved', 'rejected', 'resolved', 'expired')) not null default 'pending', requested_at datetime not null default CURRENT_TIMESTAMP, expires_at datetime, resolved_at datetime, resolved_by integer, foreign key(user_id) references users(id) on delete set null, foreign key(resolved_by) references users(id) on delete set null)");
            DB::statement('INSERT INTO password_reset_requests (id, email, user_id, status, requested_at, expires_at, resolved_at, resolved_by) SELECT id, email, user_id, status, requested_at, expires_at, resolved_at, resolved_by FROM password_reset_requests_old');
            DB::statement('DROP TABLE password_reset_requests_old');
        } else {
            // For MySQL/Postgres, alter the enum
            DB::statement("ALTER TABLE password_reset_requests MODIFY COLUMN status ENUM('pending','approved','rejected','resolved','expired') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Revert to original
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TABLE IF EXISTS password_reset_requests_old');
            DB::statement('ALTER TABLE password_reset_requests RENAME TO password_reset_requests_old');
            DB::statement("CREATE TABLE password_reset_requests (id integer primary key autoincrement not null, email varchar not null, user_id integer, status varchar check (\"status\" in ('pending', 'resolved')) not null default 'pending', requested_at datetime not null default CURRENT_TIMESTAMP, expires_at datetime, resolved_at datetime, resolved_by integer, foreign key(user_id) references users(id) on delete set null, foreign key(resolved_by) references users(id) on delete set null)");
            DB::statement('INSERT INTO password_reset_requests (id, email, user_id, status, requested_at, expires_at, resolved_at, resolved_by) SELECT id, email, user_id, status, requested_at, expires_at, resolved_at, resolved_by FROM password_reset_requests_old WHERE status IN (\'pending\',\'resolved\')');
            DB::statement('DROP TABLE password_reset_requests_old');
        } else {
            DB::statement("ALTER TABLE password_reset_requests MODIFY COLUMN status ENUM('pending','resolved') NOT NULL DEFAULT 'pending'");
        }
    }
};
