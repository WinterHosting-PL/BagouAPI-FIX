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
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropForeign(['product_id']);

            $table->dropColumn('product_id');
            $table->dropColumn('buyer');
            $table->dropColumn('buyerid');

            $table->renameColumn('name', 'product_id');
            $table->renameColumn('transaction', 'license');
            $table->integer('product_id')->unsigned()->change();

            $table->foreign('product_id')
                ->references('id')
                ->on('products');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('licenses', function (Blueprint $table) {
        $table->dropForeign(['product_id']);

        $table->renameColumn('product_id', 'name');
        $table->renameColumn('license', 'transaction');
        $table->string('name')->change();
        $table->string('buyer')->nullable();
        $table->string('buyerid')->nullable();

        $table->foreign('name')
            ->references('id')
            ->on('products');
    });
    }
};
