<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tutor_sessions DROP CONSTRAINT IF EXISTS tutor_sessions_status_check');
        }

        Schema::table('tutor_sessions', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Widening a column to a plain string is not meaningfully reversible
        // back to a narrower native enum without knowing every value already
        // stored -- intentionally left as a no-op, matching this session's
        // established convention for this exact migration shape.
    }
};
