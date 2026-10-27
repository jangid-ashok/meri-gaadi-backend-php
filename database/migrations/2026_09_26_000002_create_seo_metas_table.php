<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type', 100);
            $table->unsignedBigInteger('seoable_id');
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('robots', 30)->default('index, follow');
            $table->string('twitter_card_type', 30)->default('summary_large_image');
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->foreignId('twitter_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
            $table->index(['robots', 'updated_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('seo_metas'); }
};
