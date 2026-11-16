<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

              // ✅ UUID foreign key (correct)
            $table->uuid('chair_member_id')->nullable();

            $table->foreign('chair_member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();

            // if (Schema::hasTable('members')) {
            //     $table->foreignId('chair_member_id')->nullable()->constrained('members')->nullOnDelete();
            // } else {
            //     $table->unsignedBigInteger('chair_member_id')->nullable();
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
