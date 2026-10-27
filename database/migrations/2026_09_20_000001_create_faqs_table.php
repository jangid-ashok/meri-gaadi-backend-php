<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('slug')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('slug');
            $table->index(['category_id', 'status']);
            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
