<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('blogs')->whereNull('excerpt')->whereNotNull('sub_title')
            ->update(['excerpt' => DB::raw('sub_title')]);
    }

    public function down(): void
    {
        // Do not erase excerpt edits made after the backfill.
    }
};
