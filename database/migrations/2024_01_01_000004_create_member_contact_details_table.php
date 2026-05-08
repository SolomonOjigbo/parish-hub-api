<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_contact_details', function (Blueprint $table) {
            $table->id();
            $table->string('primary_phone');
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('lga')->nullable();
            $table->string('state')->default('Lagos');
            $table->timestamps();

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_contact_details');
    }
};
