<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')->where('report_status', 'Assigned')->update(['report_status' => 'Verified']);
    }

    public function down(): void
    {
        //
    }
};
