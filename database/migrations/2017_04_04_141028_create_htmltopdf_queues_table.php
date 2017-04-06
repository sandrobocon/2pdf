<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHtmltopdfQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('htmltopdf_queues', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hash')->unique();
            $table->integer('status');
            $table->integer('user_id');
            $table->integer('file_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('htmltopdf_queues');
    }
}
