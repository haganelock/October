<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddFiscalCodeMallCustomers extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_customers', function ($table) {
            $table->string('fiscal_code', 20)->nullable();
            $table->string('company_name', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::table('offline_mall_customers', function ($table) {
            $table->dropColumn('fiscal_code');
            $table->dropColumn('company_name');
        });
    }
}
