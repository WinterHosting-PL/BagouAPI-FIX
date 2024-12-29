<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProductsIdColumnToBiginteger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->unsignedBigInteger('extension_product');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('extension_product')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['extension_product']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('extension_product')->references('id')->on('products')->onDelete('cascade');
        });
    }
}