<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSubjectFromTicketsTable extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('subject');
            $table->dropForeign(['assignee_id']);
            $table->dropColumn('assignee_id');
            $table->dropForeign(['license']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('subject')->nullable();
            $table->integer('assignee_id')->unsigned();
            $table->foreign('license')->references('transaction')->on('license');
            $table->foreign('assignee_id')->references('id')->on('users');
        });
    }
}
