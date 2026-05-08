<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('society_members', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['member', 'president', 'vicePresident', 'secretary', 'treasurer', 'PRO', 'welfareOfficer'])->default('member');
            $table->date('joined_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            if (Schema::hasTable('societies')) {
                $table->foreignId('society_id')->constrained('societies')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('society_id');
            }

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('member_id');
            }

            $table->unique(['society_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('society_members');
    }
};
