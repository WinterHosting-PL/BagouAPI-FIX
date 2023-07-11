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
        Schema::create('users_discord', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->string('discord_id');
            $table->string('username');
            $table->string('avatar')->nullable();
            $table->string('discriminator', 4)->nullable();
            $table->string('email');
            $table->timestamps();
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
        Schema::dropIfExists('users_discord');
    }
};
