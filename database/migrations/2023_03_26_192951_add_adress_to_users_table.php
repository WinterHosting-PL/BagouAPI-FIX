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
        Schema::table('users', function (Blueprint $table) {
            $table->string('society')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('country');
            $table->string('region');
            $table->string('postal_code');
            $table->string('phone_number')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('society');
            $table->dropColumn('address');
            $table->dropColumn('region');
            $table->dropColumn('city');
            $table->dropColumn('postal_code');
            $table->dropColumn('country');
            $table->dropColumn('phone_number');


        });
    }
};
