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
        Schema::create('users_google', function(Blueprint $table) {
           $table->id();
           $table->timestamps();
           $table->integer('user_id')->unsigned();
           $table->string('username');
           $table->string('google_id');
           $table->string('avatar');
           $table->foreign('user_id')
               ->references('id')
               ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_google');
    }
};
