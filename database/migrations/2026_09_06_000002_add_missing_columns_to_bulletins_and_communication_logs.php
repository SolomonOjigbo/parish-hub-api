<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->string('title')->nullable()->after('sunday_date');
        });

        Schema::table('communication_logs', function (Blueprint $table) {
            $table->integer('sent_count')->default(0)->after('recipient_count');
            $table->integer('failed_count')->default(0)->after('sent_count');
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropColumn(['sent_count', 'failed_count', 'scheduled_at']);
        });
    }
};
