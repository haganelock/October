<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddDetailsSpecsCoverToOfflineMallProducts extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_products', function ($table) {
            $table->text('details')->nullable();
            $table->text('specs')->nullable();
            $table->string('cover', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function ($table) {
            $table->dropColumns('details');
            $table->dropColumns('specs');
            $table->dropColumns('cover');
        });
    }
}
