<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->string('role')->nullable();
            $table->timestamps();

            if (Schema::hasTable('committees')) {
                $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('committee_id');
            }

            if (Schema::hasTable('members')) {
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
