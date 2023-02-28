<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title')->nullable();
            $table->text('lead')->nullable();
            $table->text('body')->nullable();

            /*
             * nullable some authors might disappear
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * links are added to the body. we want users to click through to a news to know when they read it
             */
            // $table->string('link')->nullable();

            /*
             * images are imported and attached as media. we don't want to upset the csp rules
             */
            // $table->string('image')->nullable();

            /*
             * allows to plan ahead
             */
            $table->timestampTz('publish_at')->nullable();
            $table->timestampTz('unpublish_at')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('news');
    }
};
