<?php namespace Syncronika\GDPR\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateGdprTable extends Migration
{

    public function up()
    {
        Schema::create('syncronika_privacies', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('email')->unique();
            $table->tinyInteger('approved')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('syncronika_privacies');
    }

}
