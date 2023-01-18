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
        Schema::create('licensetable', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean('blacklisted');
            $table->string('buyer');
            $table->string('fullname');
            $table->json('ip');
            $table->integer('maxusage');
            $table->integer('name');
            $table->string('transaction');
            $table->integer('usage');
            $table->string('buyerid');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('licensetable');
    }
};
