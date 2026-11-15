<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rep_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uuid')->unique();
            $table->string('number', 30);
            $table->enum('payable_type', ['customer', 'supplier']);
            $table->unsignedBigInteger('payable_id');
            $table->enum('direction', ['in', 'out']);
            $table->enum('method', ['cash', 'cheque', 'transfer']);
            $table->decimal('amount', 14, 2);
            $table->text('note')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
