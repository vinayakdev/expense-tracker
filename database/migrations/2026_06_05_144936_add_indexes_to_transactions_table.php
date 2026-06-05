<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Covers the main list query: filter by account + sort/filter by date
            $table->index(['account_id', 'transacted_at']);
            // Covers expense-only queries (widget insights, summary chart)
            $table->index(['account_id', 'type', 'transacted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'transacted_at']);
            $table->dropIndex(['account_id', 'type', 'transacted_at']);
        });
    }
};
