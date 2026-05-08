<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('donor_name')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->decimal('amount', 10, 2);
            $table->string('purpose');
            $table->date('donation_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'pos', 'cheque']);
            $table->string('transfer_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('member_id')->nullable();
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
        Schema::dropIfExists('donations');
    }
};
