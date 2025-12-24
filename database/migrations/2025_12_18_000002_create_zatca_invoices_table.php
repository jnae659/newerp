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
        Schema::create('zatca_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('invoice_id')->nullable(); // Reference to original invoice
            $table->string('zatca_uuid')->unique(); // ZATCA unique identifier
            $table->string('zatca_invoice_number')->unique(); // ZATCA formatted invoice number
            $table->string('zatca_invoice_type')->default('standard'); // standard, simplified, credit_note, debit_note
            $table->string('zatca_status')->default('draft'); // draft, valid, invalid, cancelled
            $table->json('zatca_data')->nullable(); // Complete ZATCA invoice data
            $table->json('zatca_response')->nullable(); // ZATCA API response
            $table->string('zatca_qr_code')->nullable(); // QR code data
            $table->string('zatca_digital_signature')->nullable(); // Digital signature
            $table->timestamp('zatca_submitted_at')->nullable();
            $table->timestamp('zatca_validated_at')->nullable();
            $table->text('zatca_error_message')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_invoices');
    }
};
