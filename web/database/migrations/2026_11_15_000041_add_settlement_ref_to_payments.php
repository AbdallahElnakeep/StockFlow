<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('rep_settlement_id')->after('method')->nullable()->constrained()->nullOnDelete();
            $table->index(['rep_user_id', 'rep_settlement_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['rep_user_id', 'rep_settlement_id']);
            $table->dropConstrainedForeignId('rep_settlement_id');
        });
    }
};
