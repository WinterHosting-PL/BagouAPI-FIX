<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyBbbLicenseInLicensesTable extends Migration
{
    public function up()
    {
        Schema::table('license', function (Blueprint $table) {
            $table->text('bbb_license')->nullable()->default(null)->change();
            $table->text('bbb_id')->nullable()->default(null)->change();
            $table->integer('sxcid')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('license', function (Blueprint $table) {
            $table->text('bbb_license')->nullable(false)->default('')->change();
            $table->text('bbb_id')->nullable(false)->default('')->change();
            $table->integer('sxcid')->nullable(false)->default(0)->change();
        });
    }
}