<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('user_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chain_id');
            $table->unsignedBigInteger('author_id');
            $table->text('body');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('chain_id');

            $table->foreign('chain_id')->references('ID')->on('user_message_chains')->onDelete('cascade');
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        Schema::table('Messages', function (Blueprint $table) {
            $table->unsignedBigInteger('migrated_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('Messages', function (Blueprint $table) {
            $table->dropColumn('migrated_id');
        });

        Schema::dropIfExists('user_messages');
    }
};
