<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('uuid')->unique();
            $table->string('ref_type', 40);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->decimal('qty', 14, 3);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('balance_after', 14, 3);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'ref_type', 'ref_id']);
            $table->index(['warehouse_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
