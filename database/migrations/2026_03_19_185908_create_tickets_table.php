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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('reporter_phone')->nullable();
            $table->foreignId('reporter_organization_id')->nullable()->constrained('organizations');
            $table->string('reporter_organization_name')->nullable();
            $table->dateTime('reported_at')->default(now());
            $table->string('report_status')->default('Pending');
            $table->boolean('report_is_valid')->default(true);
            $table->dateTime('incident_time')->default(now());
            $table->string('incident_severity')->nullable();
            $table->text('incident_description');
            $table->string('status')->default('Open');
            $table->string('sub_status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
