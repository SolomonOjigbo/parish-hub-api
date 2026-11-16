<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->uuid('id')->primary();
            #$table->id();
            $table->string('name');
             // ✅ FIX: UUID instead of BIGINT
            $table->uuid('head_member_id')->nullable();
           // $table->unsignedBigInteger('head_member_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

             // ✅ family → member (UUID FK)
            $table->foreign('head_member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();

            // ✅ zone relation (UUID FK)
            $table->uuid('zone_id')->nullable();

            $table->foreign('zone_id')
                ->references('id')
                ->on('zones')
                ->nullOnDelete();

            // if (Schema::hasTable('zones')) {
            //     #$table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            //     $table->uuid('zone_id');
            //     $table->foreign('zone_id')
            //         ->references('id')
            //         ->on('zones')
            //         ->onDelete('cascade');
            // } else {
            //     $table->unsignedBigInteger('zone_id')->nullable();
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
