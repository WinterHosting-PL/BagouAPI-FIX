<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('attachments');

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->unsignedBigInteger('ticket_id');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->string('unique_name');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ticket_id')->references('id')->on('tickets');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
