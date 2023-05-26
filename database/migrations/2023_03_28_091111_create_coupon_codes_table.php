<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponCodesTable extends Migration
{
    public function up()
    {
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('value');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_codes');
    }
}