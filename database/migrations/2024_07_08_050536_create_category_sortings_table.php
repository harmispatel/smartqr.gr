<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategorySortingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_sortings', function (Blueprint $table) {
            $table->id();
            $table->longText('category_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('order_key')->nullable();
            $table->integer('shop_id')->nullable();
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
        Schema::dropIfExists('category_sortings');
    }
}
