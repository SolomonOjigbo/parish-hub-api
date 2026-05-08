<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->timestamp('registered_at');
            $table->enum('payment_status', ['na', 'pending', 'paid'])->default('na');
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            if (Schema::hasTable('events')) {
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('event_id');
            }

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('member_id');
            }

            $table->unique(['event_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
