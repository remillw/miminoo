<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->enum('status', ['active', 'awaiting_payment', 'booked', 'completed', 'cancelled'])->default('active');
            $table->boolean('is_boosted')->default(false);
            $table->unsignedBigInteger('confirmed_application_id')->nullable(); // TEMP sans FK
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
