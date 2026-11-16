<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacramental_records', function (Blueprint $table) {
            #$table->id();
            $table->uuid('id')->primary();
            $table->enum('type', ['baptism', 'first_communion', 'confirmation', 'marriage', 'holy_orders']);
            $table->date('date')->nullable();
            $table->string('church')->nullable();
            $table->string('minister')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('certificate_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // if (Schema::hasTable('members')) {
            //     $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // } else {
            //     $table->unsignedBigInteger('member_id');
            // }

              $table->uuid('member_id');

    $table->foreign('member_id')
        ->references('id')
        ->on('members')
        ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacramental_records');
    }
};
