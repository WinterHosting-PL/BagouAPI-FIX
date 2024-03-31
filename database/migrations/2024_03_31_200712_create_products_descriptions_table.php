<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('language');
            $table->text('description');
            $table->string('tag');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products_descriptions');
    }
};
