<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['mass', 'society_meeting', 'retreat', 'fundraiser', 'feast_day', 'diocesan', 'other']);
            $table->text('description')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->integer('max_capacity')->nullable();
            $table->boolean('requires_registration')->default(false);
            $table->boolean('is_retreat')->default(false);
            $table->decimal('retreat_fee', 10, 2)->nullable();
            $table->text('accommodation_notes')->nullable();
            $table->boolean('include_in_bulletin')->default(false);
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('users')) {
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
