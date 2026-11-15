<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('cheque_no', 60)->nullable()->after('method');
            $table->date('due_date')->nullable()->after('cheque_no');
            $table->index(['company_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'due_date']);
            $table->dropColumn(['cheque_no', 'due_date']);
        });
    }
};
