<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add recurrent (bool) to product table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('recurrent')->default(false);
        });

        // Replace product_id (int) by products (array) in orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('product_id');
            $table->json('products');
        });

        // Rename column "mollie_id" to "stripe_id" in orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('mollie_id', 'stripe_id');
        });
        // Rename "license" table to "licenses" table
        Schema::rename('license', 'licenses');
        // Add product_id to licenses table as a foreign key
        Schema::table('products', function (Blueprint $table) {
            $table->primary('id');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('fullname');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');

        });


        // Add address, country, city, region, postal_code, and name to orders table (non-null)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('address');
            $table->string('country');
            $table->string('city');
            $table->string('region');
            $table->string('postal_code');
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('recurrent');
            $table->dropPrimary('products_id_primary');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('products');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('stripe_id', 'mollie_id');
        });

        Schema::rename('licenses', 'license');

        Schema::table('products', function (Blueprint $table) {
            $table->dropPrimary('products_id_primary');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->string('name');
            $table->string('fullname');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('country');
            $table->dropColumn('city');
            $table->dropColumn('region');
            $table->dropColumn('postal_code');
            $table->dropColumn('name');
        });
    }

};
