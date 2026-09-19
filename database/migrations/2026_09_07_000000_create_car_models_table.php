<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->unsignedSmallInteger('launch_year')->nullable();
            $table->unsignedSmallInteger('discontinued_year')->nullable();
            $table->string('body_type', 40)->nullable()->index();
            $table->json('fuel_types')->nullable();
            $table->json('transmission_types')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_models');
    }
};