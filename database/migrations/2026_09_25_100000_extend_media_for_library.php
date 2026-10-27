<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('blog_id');
            $table->string('extension', 12)->nullable()->after('mime');
            $table->unsignedInteger('width')->nullable()->after('size');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->string('alt_text')->nullable()->after('height');
            $table->string('title')->nullable()->after('alt_text');
            $table->string('folder')->nullable()->after('title');
            $table->foreignId('created_by')->nullable()->after('folder')->constrained('users')->nullOnDelete();
            $table->string('thumbnail_path')->nullable()->after('created_by');
            $table->index(['created_by', 'created_at'], 'media_creator_created_index');
            $table->index(['extension', 'created_at'], 'media_extension_created_index');
            $table->index(['disk', 'path'], 'media_disk_path_index');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex('media_creator_created_index');
            $table->dropIndex('media_extension_created_index');
            $table->dropIndex('media_disk_path_index');
            $table->dropColumn(['original_name', 'extension', 'width', 'height', 'alt_text', 'title', 'folder', 'created_by', 'thumbnail_path']);
        });
    }
};
