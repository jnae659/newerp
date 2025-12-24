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
        Schema::create('accountant_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('accountant_id');
            $table->string('email');
            $table->string('status')->default('pending'); // pending, accepted, rejected, cancelled
            $table->json('permissions')->nullable(); // Store permission levels
            $table->text('message')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accountant_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['company_id', 'accountant_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountant_invitations');
    }
};
