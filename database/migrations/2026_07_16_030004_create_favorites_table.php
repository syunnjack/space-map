<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            // vacancy_slots.idを検知カーソルとして使う。created_at(秒精度)の比較では
            // 同一秒内に複数件投稿されると取りこぼす恐れがあるため、常に厳密単調増加するidを使う。
            $table->unsignedBigInteger('last_checked_slot_id')->nullable();
            $table->timestamps();

            $table->unique(['line_user_id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
