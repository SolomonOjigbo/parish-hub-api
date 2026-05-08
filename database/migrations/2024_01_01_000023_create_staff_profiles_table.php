<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('job_title');
            $table->enum('employment_type', ['employed', 'volunteer']);
            $table->date('start_date')->nullable();
            $table->integer('annual_leave_days')->default(0);
            $table->timestamps();

            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('user_id');
            }

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('member_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
