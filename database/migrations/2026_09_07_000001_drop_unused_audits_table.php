<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The owen-it/laravel-auditing package was removed; the app's own
     * audit_logs table is the single audit store. This drops the never-
     * written package table.
     */
    public function up(): void
    {
        Schema::dropIfExists('audits');
    }

    public function down(): void
    {
        // Intentionally left empty — the package is gone.
    }
};
