<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddPhoneNumberMallCustomers extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_customers', function ($table) {
            $table->string('phone_number', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_customers', function ($table) {
            $table->dropColumn('phone_number');
        });
    }
}
