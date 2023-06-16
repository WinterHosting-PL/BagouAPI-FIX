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
        Schema::table('license', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->string('transaction')->primary()->change();
        });

        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->text('subject');
            $table->integer('user_id')->unsigned();
            $table->string('license');
            $table->text('logs_url')->nullable();
            $table->json('participants')->nullable();

            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users');

            $table->foreign('license')->references('transaction')->on('license');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->integer('user_id')->unsigned();
            $table->text('content');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ;

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

        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};