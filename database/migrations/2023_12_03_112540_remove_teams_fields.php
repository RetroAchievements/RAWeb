<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (Schema::hasColumn($tableNames['roles'], $columnNames['team_foreign_key'])) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($columnNames) {
                $table->dropIndex('roles_team_foreign_key_index');
                $table->dropIndex('auth_roles_team_id_name_guard_name_unique');
                $table->dropColumn($columnNames['team_foreign_key']);
            });
        }

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->unique(['name', 'guard_name']);
        });

        if (Schema::hasColumn($tableNames['model_has_permissions'], $columnNames['team_foreign_key'])) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames, $pivotPermission) {
                $table->dropIndex('model_has_permissions_team_foreign_key_index');
                $table->dropPrimary();
                $table->dropColumn($columnNames['team_foreign_key']);
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type']);
            });
        }

        if (Schema::hasColumn($tableNames['model_has_roles'], $columnNames['team_foreign_key'])) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames, $pivotRole) {
                $table->dropIndex('model_has_roles_team_foreign_key_index');
                $table->dropPrimary();
                $table->dropColumn($columnNames['team_foreign_key']);

                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // nothing to revert
    }
};
