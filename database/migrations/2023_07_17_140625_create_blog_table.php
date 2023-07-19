<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new  class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('blog', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->string('title');
          $table->unsignedBigInteger('category_id');
            $table->unsignedInteger('views')->default(0);
            $table->json('tags');
            $table->string('slug');
            $table->json('pictures');
            $table->longText('content');
            $table->timestamps();
            $table->foreign('user_id')
               ->references('id')
               ->on('users');
                        $table->foreign('category_id')->references('id')->on('categories');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blog');
        Schema::dropIfExists('categories');

    }
};
