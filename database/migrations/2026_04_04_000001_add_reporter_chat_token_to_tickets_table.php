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
            $table->string('reporter_chat_token_hash')->nullable()->after('reopened_at');
            $table->timestamp('reporter_chat_token_created_at')->nullable()->after('reporter_chat_token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'reporter_chat_token_hash',
                'reporter_chat_token_created_at',
            ]);
        });
    }
};
