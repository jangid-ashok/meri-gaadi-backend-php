<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key', 40)->unique();
            $table->string('title', 255)->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedSmallInteger('item_limit')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            $table->index(['enabled', 'display_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('homepage_sections'); }
};
