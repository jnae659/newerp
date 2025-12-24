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
        Schema::create('zatca_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('zatca_enabled')->default('off');
            $table->string('zatca_phase')->nullable(); // 'phase1', 'phase2'
            $table->string('zatca_api_endpoint')->nullable();
            $table->string('zatca_api_key')->nullable();
            $table->string('zatca_api_secret')->nullable();
            $table->string('zatca_certificate_path')->nullable();
            $table->string('zatca_private_key_path')->nullable();
            $table->string('zatca_tax_number')->nullable();
            $table->string('zatca_branch_code')->nullable();
            $table->string('zatca_device_id')->nullable();
            $table->string('zatca_pos_device')->nullable();
            $table->text('zatca_settings')->nullable(); // JSON settings
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_configurations');
    }
};
