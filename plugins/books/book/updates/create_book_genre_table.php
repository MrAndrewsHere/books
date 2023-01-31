<?php

namespace Books\Book\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class CreateBookGenreTable extends Migration
{

    public function up()
    {
        Schema::create('books_book_genre', function (Blueprint $table) {
            $table->integer('book_id')->unsigned()->index();
            $table->integer('genre_id')->unsigned()->index();
            $table->integer('rate_number')->nullable();
            $table->json('rate_history')->nullable();
            $table->primary(['book_id', 'genre_id']);
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books_book_genre');
    }
}
