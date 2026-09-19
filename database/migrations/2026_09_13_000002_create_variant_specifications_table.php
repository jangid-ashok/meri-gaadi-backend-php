<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->restrictOnDelete();
            $table->foreignId('specification_definition_id')->constrained('specification_definitions')->restrictOnDelete();
            $table->string('value');
            $table->timestamps();

            $table->unique(['variant_id', 'specification_definition_id'], 'variant_spec_unique');
            $table->index(['variant_id', 'specification_definition_id'], 'variant_spec_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_specifications');
    }
};
