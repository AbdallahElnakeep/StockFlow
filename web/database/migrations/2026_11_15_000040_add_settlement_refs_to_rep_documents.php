<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('rep_settlement_id')->after('collected_amount')->nullable()->constrained()->nullOnDelete();
            $table->index(['rep_user_id', 'rep_settlement_id']);
        });

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->foreignId('rep_settlement_id')->after('total')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rep_settlement_id');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['rep_user_id', 'rep_settlement_id']);
            $table->dropConstrainedForeignId('rep_settlement_id');
        });
    }
};
