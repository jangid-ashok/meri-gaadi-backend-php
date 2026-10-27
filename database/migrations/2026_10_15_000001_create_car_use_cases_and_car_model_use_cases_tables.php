<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_use_cases', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('car_model_use_cases', function (Blueprint $table) {
            $table->foreignId('car_model_id')->constrained('car_models')->cascadeOnDelete();
            $table->foreignId('car_use_case_id')->constrained('car_use_cases')->cascadeOnDelete();
            $table->primary(['car_model_id', 'car_use_case_id']);
            $table->index(['car_use_case_id', 'car_model_id']);
        });

        $timestamp = now();
        DB::table('car_use_cases')->insert(collect(config('constants.CAR_USE_CASES'))
            ->map(fn (string $label, string $slug) => [
                'slug' => $slug,
                'label' => $label,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('car_model_use_cases');
        Schema::dropIfExists('car_use_cases');
    }
};
