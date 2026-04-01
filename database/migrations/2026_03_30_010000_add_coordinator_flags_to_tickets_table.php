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
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'handling_validated_at')) {
                $table->timestamp('handling_validated_at')->nullable()->after('closed_at');
            }

            if (! Schema::hasColumn('tickets', 'handling_validated_by')) {
                $table->foreignId('handling_validated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('handling_validated_at');
            }

            if (! Schema::hasColumn('tickets', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable()->after('handling_validated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'handling_validated_by')) {
                $table->dropForeign(['handling_validated_by']);
                $table->dropColumn('handling_validated_by');
            }

            if (Schema::hasColumn('tickets', 'handling_validated_at')) {
                $table->dropColumn('handling_validated_at');
            }

            if (Schema::hasColumn('tickets', 'reopened_at')) {
                $table->dropColumn('reopened_at');
            }
        });
    }
};

