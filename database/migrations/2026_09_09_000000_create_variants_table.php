<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_model_id')->constrained('car_models')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('variant_code')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['car_model_id', 'slug']);
            $table->unique(['car_model_id', 'name']);
            $table->unique(['car_model_id', 'variant_code']);
            $table->index(['car_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};