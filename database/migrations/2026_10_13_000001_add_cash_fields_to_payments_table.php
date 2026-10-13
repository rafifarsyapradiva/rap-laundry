<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('cash_tendered', 12, 2)->nullable()->after('method');
            $table->decimal('change_amount', 12, 2)->nullable()->after('cash_tendered');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_cash_tendered CHECK (cash_tendered IS NULL OR cash_tendered >= amount)');
            DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_change_amount CHECK (change_amount IS NULL OR change_amount >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_payments_cash_tendered');
            DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_payments_change_amount');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cash_tendered', 'change_amount']);
        });
    }
};
