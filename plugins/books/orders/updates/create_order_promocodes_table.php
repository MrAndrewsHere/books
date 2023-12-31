<?php namespace Books\Orders\Updates;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateOrdersTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_orders_promocodes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedBigInteger('promocode_id');
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('books_orders_orders')
                ->onDelete('cascade');

            $table->foreign('promocode_id')
                ->references('id')
                ->on('books_book_promocodes')
                ->onDelete('cascade');

            $table->unique(['order_id', 'promocode_id']);
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_orders_promocodes');
    }
};
