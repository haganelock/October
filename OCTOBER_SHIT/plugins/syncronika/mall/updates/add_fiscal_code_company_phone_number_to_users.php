<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddFiscalCodeCompanyPhoneNumberToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->string('fiscal_code', 20)->nullable();
            $table->string('company_name', 100)->nullable();
            $table->string('phone_number', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('fiscal_code');
            $table->dropColumn('company_name');
            $table->dropColumn('phone_number');
        });
    }
}
