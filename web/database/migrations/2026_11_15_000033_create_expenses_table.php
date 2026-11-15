<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rep_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rep_settlement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('uuid')->unique();
            $table->string('category', 60);
            $table->decimal('amount', 14, 2);
            $table->string('note')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->index(['company_id', 'rep_user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
