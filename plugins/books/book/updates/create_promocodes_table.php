<?php namespace Books\Book\Updates;

use Books\Book\Models\Promocode;
use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreatePromocodesTable Migration
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration {
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('books_book_promocodes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code', Promocode::CODE_LENGTH)->unique();

            $table->unsignedBigInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books_book_books')->cascadeOnDelete();

            $table->unsignedInteger('profile_id');
            $table->foreign('profile_id')->references('id')->on('books_profile_profiles')->cascadeOnDelete();

            $table->boolean('is_activated')->default(false);

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->dateTime('activated_at')->nullable();
            $table->timestamp('expire_in');
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('books_book_promocodes');
    }
};
