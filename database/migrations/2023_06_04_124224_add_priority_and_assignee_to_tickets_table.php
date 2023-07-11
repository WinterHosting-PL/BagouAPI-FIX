<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorityAndAssigneeToTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('priority', ['very_low', 'low', 'normal', 'high', 'very_high'])->default('normal');
            $table->integer('assignee_id')->unsigned();
            $table->foreign('assignee_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
            $table->dropColumn('assignee_id');
            $table->dropColumn('priority');
        });
    }
}
