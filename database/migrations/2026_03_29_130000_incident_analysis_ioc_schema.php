<?php

/**
 * Aligns DB with ERD: incident_ioc_types, incident_analyses, incident_ioc.
 *
 * Forward-only additive when tables already exist (e.g. external IOC tables):
 * adds missing columns / FKs without renaming or dropping legacy columns.
 *
 * Rollback (down) drops incident_ioc, incident_analyses, and incident_ioc_types entirely;
 * avoid migrate:rollback if incident_ioc_types was pre-existing with unrelated data.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incident_ioc_types')) {
            Schema::create('incident_ioc_types', function (Blueprint $table) {
                $table->id();
                $table->string('ioc_type')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('incident_ioc_types', function (Blueprint $table) {
                if (! Schema::hasColumn('incident_ioc_types', 'description')) {
                    $table->text('description')->nullable();
                }
            });
        }

        if (! Schema::hasTable('incident_analyses')) {
            Schema::create('incident_analyses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
                $table->string('severity')->nullable();
                $table->text('impact')->nullable();
                $table->text('root_cause')->nullable();
                $table->text('recommendation')->nullable();
                $table->text('analysis_result')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('incident_ioc')) {
            Schema::create('incident_ioc', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('analysis_id')->constrained('incident_analyses')->cascadeOnDelete();
                $table->foreignId('incident_ioc_type_id')->constrained('incident_ioc_types')->restrictOnDelete();
                $table->text('value');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('incident_ioc', function (Blueprint $table) {
                if (! Schema::hasColumn('incident_ioc', 'public_id')) {
                    $table->uuid('public_id')->nullable()->unique()->after('id');
                }
                if (! Schema::hasColumn('incident_ioc', 'analysis_id')) {
                    $table->foreignId('analysis_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('incident_analyses')
                        ->cascadeOnDelete();
                }
                if (! Schema::hasColumn('incident_ioc', 'incident_ioc_type_id')) {
                    $table->foreignId('incident_ioc_type_id')
                        ->nullable()
                        ->constrained('incident_ioc_types')
                        ->restrictOnDelete();
                }
                if (! Schema::hasColumn('incident_ioc', 'value')) {
                    $table->text('value')->nullable();
                }
                if (! Schema::hasColumn('incident_ioc', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('incident_ioc', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('incident_ioc', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_ioc');
        Schema::dropIfExists('incident_analyses');
        Schema::dropIfExists('incident_ioc_types');
    }
};
