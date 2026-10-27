<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('description')->index();
            $table->integer('sort_order')->default(0)->after('status')->index();
            $table->softDeletes();
            $table->unique('name');
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropIndex(['status', 'sort_order']);
            $table->dropSoftDeletes();
            $table->dropColumn(['description', 'status', 'sort_order']);
        });
    }
};