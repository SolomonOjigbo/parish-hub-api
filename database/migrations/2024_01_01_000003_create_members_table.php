<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_name')->nullable();
            $table->string('baptismal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'widowed', 'divorced']);
            $table->string('occupation')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_family_head')->default(false);
            $table->enum('status', ['active', 'inactive', 'transferred', 'deceased'])->default('active');
            $table->date('date_joined')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('families')) {
                $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('family_id')->nullable();
            }

            if (Schema::hasTable('zones')) {
                $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('zone_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
