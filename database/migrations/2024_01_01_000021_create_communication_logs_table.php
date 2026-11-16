<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->enum('type', ['email', 'sms']);
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('recipient_type', ['all', 'society', 'zone', 'individual']);
            $table->json('recipient_ids');
            $table->integer('recipient_count');
            $table->enum('status', ['pending', 'sent', 'failed', 'partial'])->default('pending');
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

              // ✅ FIXED UUID FK
            $table->uuid('sent_by');
            $table->foreign('sent_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // if (Schema::hasTable('users')) {
            //     $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('sent_by');
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
