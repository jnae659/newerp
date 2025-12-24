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
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->decimal('level1_percentage', 5, 2)->default(0)->after('percentage');
            $table->decimal('level2_percentage', 5, 2)->default(0)->after('level1_percentage');
            $table->renameColumn('minimum_threshold_amount', 'min_payout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->dropColumn(['level1_percentage', 'level2_percentage']);
            $table->renameColumn('min_payout', 'minimum_threshold_amount');
        });
    }
};
