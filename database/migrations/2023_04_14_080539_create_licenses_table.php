<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->decimal('new_version', 8, 2)->nullable();
            $table->boolean('patreon')->nullable()->default(false);
            $table->string('transaction', 255)->primary();
            $table->timestamps();
            $table->boolean('blacklisted')->default(false);
            $table->json('ip')->nullable();
            $table->integer('maxusage')->default(0);
            $table->integer('usage')->default(0);
            $table->string('bbb_license');
            $table->string('bbb_id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('order_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->text('bbb_license')->nullable()->default(null)->change();
            $table->text('bbb_id')->nullable()->default(null)->change();
            $table->integer('sxcid')->nullable()->default(null)->change();
            $table->decimal('version')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
