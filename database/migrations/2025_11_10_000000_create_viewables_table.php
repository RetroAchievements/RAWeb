<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('viewables', function (Blueprint $table) {
            $table->id();
            $table->string('viewable_type');
            $table->unsignedBigInteger('viewable_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('viewed_at');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->unique(['viewable_type', 'viewable_id', 'user_id'], 'viewables_viewable_type_viewable_id_user_id_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewables');
    }
};
