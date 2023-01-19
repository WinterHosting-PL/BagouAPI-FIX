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
        Schema::create('modpacksversionsresult', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('page');
            $table->string('modid');
            $table->json('result');
            $table->string('type')->default('curseforge');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modpacksversionsresult');
    }
};
