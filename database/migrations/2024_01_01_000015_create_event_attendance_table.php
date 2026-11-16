<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendance', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->timestamp('checked_in_at');
            $table->timestamps();

            // ✅ UUID FIX
            $table->uuid('event_id');
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->cascadeOnDelete();

            $table->uuid('member_id');
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();

            $table->uuid('recorded_by');
            $table->foreign('recorded_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // if (Schema::hasTable('events')) {
            //     $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('event_id');
            // }

            // if (Schema::hasTable('members')) {
            //     $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('member_id');
            // }

            // if (Schema::hasTable('users')) {
            //     $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('recorded_by');
            // }

            $table->unique(['event_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendance');
    }
};
