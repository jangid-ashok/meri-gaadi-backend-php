<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->string('value');
            $table->timestamps();

            $table->unique(['variant_id', 'feature_id'], 'variant_feature_unique');
            $table->index(['variant_id', 'feature_id'], 'variant_feature_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_features');
    }
};
