<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rep_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uuid')->unique();
            $table->string('number', 30);
            $table->date('settlement_date');
            $table->decimal('sales_cash_total', 14, 2)->default(0);
            $table->decimal('sales_credit_total', 14, 2)->default(0);
            $table->decimal('collections_total', 14, 2)->default(0);
            $table->decimal('returns_total', 14, 2)->default(0);
            $table->decimal('expenses_total', 14, 2)->default(0);
            $table->decimal('returned_stock_total', 14, 2)->default(0);
            $table->decimal('cash_handover', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->enum('status', ['draft', 'settled'])->default('draft');
            $table->text('note')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_settlements');
    }
};
