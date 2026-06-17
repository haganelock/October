<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddFiscalCodeMallOrders extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_orders', function ($table) {
            $table->string('fiscal_code', 20)->nullable();
            $table->tinyInteger('flag_company')->default(0);
        });
    }

    public function down()
    {
        Schema::table('offline_mall_orders', function ($table) {
            $table->dropColumn('fiscal_code');
            $table->dropColumn('flag_company');
        });
    }
}

