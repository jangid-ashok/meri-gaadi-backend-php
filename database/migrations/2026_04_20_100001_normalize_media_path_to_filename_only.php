<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NormalizeMediaPathToFilenameOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $rows = DB::table('media')->where('path', 'like', '%/%')->get(['id', 'path']);

        foreach ($rows as $row) {
            DB::table('media')->where('id', $row->id)->update([
                'path' => basename($row->path),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cannot restore full paths without blog_id context in a safe way.
    }
}
