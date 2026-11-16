<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_action_items', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

              // ✅ UUID foreign keys

            $table->uuid('committee_id');

            $table->foreign('committee_id')
                ->references('id')
                ->on('committees')
                ->cascadeOnDelete();

            $table->uuid('assigned_to_member_id')->nullable();

            $table->foreign('assigned_to_member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();

            // if (Schema::hasTable('committees')) {
            //     $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('committee_id');
            // }

            // if (Schema::hasTable('members')) {
            //     $table->foreignId('assigned_to_member_id')->nullable()->constrained('members')->nullOnDelete();
            // } else {
            //     $table->unsignedBigInteger('assigned_to_member_id')->nullable();
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_action_items');
    }
};
