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
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('avatar');
            $table->integer('experience_years')->nullable()->after('bio');
            $table->string('certifications')->nullable()->after('experience_years');
            $table->string('education')->nullable()->after('certifications');
            $table->json('languages')->nullable()->after('education');
            $table->json('specialties')->nullable()->after('languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['experience_years', 'certifications', 'education', 'languages', 'specialties']);
        });
    }
};
