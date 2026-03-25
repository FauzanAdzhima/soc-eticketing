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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('reporter_phone')->nullable();
            $table->foreignId('reporter_organization_id')->nullable()->constrained('organizations');
            $table->dateTime('reported_at')->default(now());
            $table->dateTime('incident_time')->default(now());
            $table->string('incident_title');
            $table->string('incident_category');
            $table->string('incident_severity')->nullable();
            $table->text('incident_description');
            $table->string('report_status')->default('Pending');
            $table->boolean('is_valid')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
