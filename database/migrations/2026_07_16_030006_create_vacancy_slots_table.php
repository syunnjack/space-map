<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->date('available_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('comment')->nullable();
            $table->string('nickname', 30)->default('匿名');
            $table->string('ip_hash', 64);
            $table->timestamps();

            $table->index(['venue_id', 'available_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_slots');
    }
};
