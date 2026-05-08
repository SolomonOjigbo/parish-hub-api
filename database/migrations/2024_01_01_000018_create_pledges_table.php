<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->string('purpose');
            $table->text('description')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('payment_frequency', ['one_off', 'monthly', 'custom']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'overdue', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('member_id');
            }

            if (Schema::hasTable('users')) {
                $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('recorded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledges');
    }
};
