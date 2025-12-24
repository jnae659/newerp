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
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id');
            $table->unsignedBigInteger('referred_user_id');
            $table->tinyInteger('level'); // 1 or 2
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->enum('status', ['registered', 'paid'])->default('registered');
            $table->unsignedBigInteger('payment_id')->nullable(); // link to invoice_payment or similar
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('users');
            $table->foreign('referred_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_referrals');
    }
};
