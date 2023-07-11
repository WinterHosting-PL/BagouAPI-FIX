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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('discord_id')->nullable();
            $table->string('discord_user_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable()->change();
        });
        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->string('discord_id')->nullable();
            $table->string('discord_user_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable()->change();
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('discord_id')->nullable();
            $table->string('discord_user_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('discord_id')->nullable();
        });
        Schema::rename('attachments', 'ticket_attachments');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('discord_id');
            $table->dropColumn('discord_user_id');
            $table->integer('user_id')->unsigned()->change();
        });
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->string('discord_id')->nullable();
            $table->string('discord_user_id')->nullable();
            $table->integer('user_id')->unsigned()->nullable()->change();
        });
        Schema::table('tickets_messages', function (Blueprint $table) {
            $table->dropColumn('discord_id');
            $table->dropColumn('discord_user_id');
            $table->integer('user_id')->unsigned()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('discord_id');
        });
        Schema::rename('ticket_attachments', 'attachments');
    }
};
