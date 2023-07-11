<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUsersColumnsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('society')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->string('region')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
            $table->string('firstname')->nullable()->change();
            $table->string('lastname')->nullable()->change();

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
            $table->string('society')->change();
            $table->string('address')->change();
            $table->string('city')->change();
            $table->string('country')->change();
            $table->string('region')->change();
            $table->string('postal_code')->change();
            $table->string('phone_number')->change();
            $table->string('firstname')->change();
            $table->string('lastname')->change();
        });
    }
}