<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('head_member_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('zones')) {
                $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('zone_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
