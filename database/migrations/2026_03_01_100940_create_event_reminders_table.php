<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('remind_at');
            $table->string('type'); // 'absolute' or 'relative'
            $table->unsignedInteger('minutes_before')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['remind_at', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reminders');
    }
};
