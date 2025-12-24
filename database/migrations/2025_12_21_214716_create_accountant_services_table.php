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
        Schema::create('accountant_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountant_id');
            $table->string('service_name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->decimal('fixed_rate', 10, 2)->nullable();
            $table->json('specialties')->nullable();
            $table->integer('experience_years')->nullable();
            $table->string('certifications')->nullable();
            $table->string('education')->nullable();
            $table->json('languages')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('accountant_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['accountant_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountant_services');
    }
};
