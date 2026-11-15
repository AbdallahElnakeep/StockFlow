<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rep_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('uuid')->unique();
            $table->enum('status', ['visited', 'no_answer', 'postponed']);
            $table->string('note')->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();
            $table->index(['company_id', 'rep_user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_visits');
    }
};
