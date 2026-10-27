<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('featured_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('cta_text', 100)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('placement', 40)->index();
            $table->enum('status', ['active', 'inactive'])->default('inactive')->index();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['placement', 'status', 'display_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('featured_contents'); }
};
