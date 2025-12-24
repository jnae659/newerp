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
        Schema::table('accountant_services', function (Blueprint $table) {
            $table->dropColumn(['experience_years', 'certifications', 'education', 'languages', 'specialties', 'bio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accountant_services', function (Blueprint $table) {
            $table->integer('experience_years')->nullable()->after('fixed_rate');
            $table->string('certifications')->nullable()->after('experience_years');
            $table->string('education')->nullable()->after('certifications');
            $table->json('languages')->nullable()->after('education');
            $table->json('specialties')->nullable()->after('languages');
            $table->text('bio')->nullable()->after('specialties');
        });
    }
};
