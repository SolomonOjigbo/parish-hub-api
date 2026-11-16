<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->string('role')->nullable();
            $table->timestamps();

              // ✅ UUID foreign keys
            $table->uuid('committee_id');
            $table->uuid('member_id');

            $table->foreign('committee_id')
                ->references('id')
                ->on('committees')
                ->cascadeOnDelete();

            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();

            // if (Schema::hasTable('committees')) {
            //     $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('committee_id');
            // }

            // if (Schema::hasTable('members')) {
            //     $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('member_id');
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
