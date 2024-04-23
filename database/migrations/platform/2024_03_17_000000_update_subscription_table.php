<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // [1] Create `id` column as the new primary key.
        // SQLite doesn't let us change a primary key after the initial migration.
        /** @see 2012_10_03_133633_create_base_tables.php */
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('Subscription', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('Subscription');

                $index = $indexesFound['subscription_subjecttype_subjectid_userid_unique'] ?? null;
                if ($index) {
                    if ($index->isUnique()) {
                        $table->dropUnique(['SubjectType', 'SubjectID', 'UserID']);
                    } else {
                        $table->dropPrimary(['SubjectType', 'SubjectID', 'UserID']);
                    }
                }
            });
            if (!Schema::hasColumn('Subscription', 'id')) {
                Schema::table('Subscription', function (Blueprint $table) {
                    $table->bigIncrements('id')->first();
                });
            }
        }

        // [2] Rename columns to align with Laravel conventions.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('SubjectType', 'subject_type');
        });
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('SubjectID', 'subject_id');
        });
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('UserID', 'user_id');
        });
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('State', 'state');
        });
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('Created', 'created_at');
        });
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('Updated', 'updated_at');
        });

        // [3] Change the datatype of `user_id` to match `UserAccounts.ID`.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        // [4] Enforce a unique constraint on type, subject_id, and user_id combos.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->unique(['subject_type', 'subject_id', 'user_id']);
        });

        // [5] Add a foreign key to UserAccounts.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        // [6] Rename the table from `Subscription` to `subscriptions`.
        Schema::rename('Subscription', 'subscriptions');
    }

    public function down(): void
    {
        // [6] Rename the table from `Subscription` to `subscriptions`.
        Schema::rename('subscriptions', 'Subscription');

        // [5] Add a foreign key to UserAccounts.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // [4] Enforce a unique constraint on type, subject_id, and user_id combos.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->dropUnique(['subject_type', 'subject_id', 'user_id']);
        });

        // [3] Change the datatype of `user_id` to match `UserAccounts.ID`.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
        });

        // [2] Rename columns to align with Laravel conventions.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->renameColumn('subject_type', 'SubjectType');
            $table->renameColumn('subject_id', 'SubjectID');
            $table->renameColumn('user_id', 'UserID');
            $table->renameColumn('state', 'State');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        // [1] Create `id` column as the new primary key.
        Schema::table('Subscription', function (Blueprint $table) {
            $table->dropColumn('id');

            $table->primary(['SubjectType', 'SubjectID', 'UserID']);
        });
    }
};
