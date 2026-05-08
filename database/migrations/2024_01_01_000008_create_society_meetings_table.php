<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('society_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('meeting_date');
            $table->time('meeting_time')->nullable();
            $table->string('venue')->nullable();
            $table->text('agenda')->nullable();
            $table->string('minutes_path')->nullable();
            $table->enum('minutes_status', ['pending', 'filed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            if (Schema::hasTable('societies')) {
                $table->foreignId('society_id')->constrained('societies')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('society_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('society_meetings');
    }
};
