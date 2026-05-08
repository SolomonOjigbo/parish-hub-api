<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->date('sunday_date');
            $table->json('content');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            if (Schema::hasTable('users')) {
                $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            } else {
                $table->unsignedBigInteger('generated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};
