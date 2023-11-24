<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('UserAccounts');

            if (!array_key_exists('users_apptoken_index', $indexesFound)) {
                $table->index(
                    ['appToken'],
                    'users_apptoken_index'
                );
            }

            if (!array_key_exists('users_apikey_index', $indexesFound)) {
                $table->index(
                    ['APIKey'],
                    'users_apikey_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('UserAccounts');

            if (array_key_exists('users_apptoken_index', $indexesFound)) {
                $table->dropIndex('users_apptoken_index');
            }

            if (array_key_exists('users_apikey_index', $indexesFound)) {
                $table->dropIndex('users_apikey_index');
            }
        });
    }
};
