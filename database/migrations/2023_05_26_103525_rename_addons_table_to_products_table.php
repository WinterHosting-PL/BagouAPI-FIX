<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameAddonsTableToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('addons', 'products');

        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->decimal('price', 10, 2)->change();
            // Modifier d'autres colonnes si nécessaire
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
            $table->string('description')->nullable()->change();
            $table->integer('price')->change();
            // Revert other column modifications if necessary
        });

        Schema::rename('products', 'addons');
    }
}
