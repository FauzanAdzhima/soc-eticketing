<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_assignments', function (Blueprint $table) {
            $table->string('kind', 32)->default('assigned_primary')->after('user_id');
            $table->index(['ticket_id', 'is_active', 'kind'], 'ticket_assignments_ticket_active_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_assignments', function (Blueprint $table) {
            $table->dropIndex('ticket_assignments_ticket_active_kind_idx');
            $table->dropColumn('kind');
        });
    }
};
