<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pledge_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'pos', 'cheque']);
            $table->string('transfer_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            if (Schema::hasTable('pledges')) {
                $table->foreignId('pledge_id')->constrained('pledges')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('pledge_id');
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
        Schema::dropIfExists('pledge_payments');
    }
};
