<?php namespace Syncronika\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddDollarPricesMallProducts extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_products', function ($table) {
            $table->decimal('price_us', 10, 2)->nullable()->default(0.00);
            $table->decimal('price_us_old', 10, 2)->nullable()->default(0.00);
        });
    }

    public function down()
    {
        Schema::table('offline_mall_products', function ($table) {
            $table->dropColumn('price_us');
            $table->dropColumn('price_us_old');
        });
    }
}
