<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->text('excerpt')->nullable()->after('sub_title');
            $table->string('featured_image')->nullable()->after('excerpt');
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->nullOnDelete();
            $table->string('publication_status', 16)->default('draft')->after('status');
            $table->index('publication_status', 'blogs_publication_status_index');
            $table->index('created_at', 'blogs_created_at_index');
        });

        DB::table('blogs')->where('status', 'active')->update(['publication_status' => 'published']);
        DB::table('blogs')->where('status', 'inactive')->update(['publication_status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex('blogs_publication_status_index');
            $table->dropIndex('blogs_created_at_index');
            $table->dropColumn(['excerpt', 'featured_image', 'category_id', 'publication_status']);
        });
    }
};
