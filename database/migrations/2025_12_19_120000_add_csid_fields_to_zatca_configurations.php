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
        Schema::table('zatca_configurations', function (Blueprint $table) {
            // CSID (Compliance Cryptographic Stamp Identifier) fields
            $table->string('zatca_csid_binary_token')->nullable()->after('zatca_certificate_path');
            $table->string('zatca_csid_secret')->nullable()->after('zatca_csid_binary_token');
            $table->string('zatca_csid_request_id')->nullable()->after('zatca_csid_secret');
            $table->string('zatca_csid_disposition')->nullable()->after('zatca_csid_request_id');
            $table->string('zatca_csid_status')->nullable()->default('NOT_SET')->after('zatca_csid_disposition');
            $table->timestamp('zatca_csid_issued_at')->nullable()->after('zatca_csid_status');
            
            // Updated private key path
            $table->string('zatca_private_key_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zatca_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'zatca_csid_binary_token',
                'zatca_csid_secret', 
                'zatca_csid_request_id',
                'zatca_csid_disposition',
                'zatca_csid_status',
                'zatca_csid_issued_at',
            ]);
        });
    }
};
