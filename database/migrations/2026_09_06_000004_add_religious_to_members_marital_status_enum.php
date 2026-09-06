<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds 'religious' (priests, consecrated religious) to marital_status,
     * matching the frontend's member model for the Catholic context.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE members MODIFY marital_status " .
                "ENUM('single','married','widowed','divorced','religious') NOT NULL"
            );

            return;
        }

        // SQLite stores enums as TEXT with a CHECK constraint; rebuilding the
        // column through the schema builder recreates the table with the
        // widened constraint.
        Schema::table('members', function ($table) {
            $table->enum('marital_status', ['single', 'married', 'widowed', 'divorced', 'religious'])->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE members MODIFY marital_status " .
                "ENUM('single','married','widowed','divorced') NOT NULL"
            );

            return;
        }

        Schema::table('members', function ($table) {
            $table->enum('marital_status', ['single', 'married', 'widowed', 'divorced'])->change();
        });
    }
};
