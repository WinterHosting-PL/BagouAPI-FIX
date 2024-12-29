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
 	Schema::rename('addon', 'addons');
        Schema::table('addons', function (Blueprint $table) {
            $table->boolean('licensed');
            $table->integer('bbb_id');
            $table->string('tag');
            $table->string('description');
            $table->json('link');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('addons', function (Blueprint $table) {
            //
        });
    }
};
