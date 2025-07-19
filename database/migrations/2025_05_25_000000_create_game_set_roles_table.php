<?php

declare(strict_types=1);

use App\Platform\Enums\GameSetRolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('game_set_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('game_set_id');
            $table->unsignedBigInteger('role_id');
            $table->enum('permission', [
                GameSetRolePermission::View->value,
                GameSetRolePermission::Update->value,
            ]);
            $table->timestamps();

            $table->unique(['game_set_id', 'role_id', 'permission']);
            $table->index(['game_set_id', 'permission']);
        });

        Schema::table('game_set_roles', function (Blueprint $table) {
            $table->foreign('game_set_id')
                ->references('id')
                ->on('game_sets')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('auth_roles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_set_roles');
    }
};
