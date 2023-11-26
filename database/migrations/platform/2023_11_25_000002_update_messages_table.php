<?php

declare(strict_types=1);

use App\Community\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // just recreate the table for the unit tests
            Schema::dropIfExists('Messages');

            Schema::create('messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('thread_id');
                $table->unsignedBigInteger('author_id');
                $table->text('body')->nullable();
                $table->timestampTz('created_at')->nullable();

                $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
                $table->foreign('thread_id')->references('ID')->on('message_threads')->onDelete('cascade');
            });

            return;
        }

        Schema::table('Messages', function (Blueprint $table) {
            $table->dropForeign('messages_recipient_id_foreign');
            $table->dropForeign('messages_sender_id_foreign');

            $table->dropColumn(['recipient_id',
                                'sender_id',
                                'read_at',
                                'Type',
                                'recipient_deleted_at',
                                'sender_deleted_at',
                                'deleted_at']);

            $table->unsignedBigInteger('thread_id')->after('ID');
            $table->unsignedBigInteger('author_id')->after('thread_id');

            $table->renameColumn('Payload', 'body');
            $table->renameColumn('TimeSent', 'created_at');
            $table->renameColumn('ID', 'id');
        });

        // populate author_id so we can add a foreign key
        DB::statement("UPDATE Messages m SET m.author_id = (SELECT u.ID FROM UserAccounts u WHERE u.User = m.UserFrom)");

        // delete records associated to non-existant users
        DB::statement("DELETE FROM Messages WHERE author_id=0");

        // add the foreign key
        Schema::table('Messages', function (Blueprint $table) {
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        Schema::rename('Messages', 'messages_');
        Schema::rename('messages_', 'messages');

        // cannot add the thread_id foreign key if there are any un-sync'd
        // records. the `ra:sync:messages` command will add it when it's done.
        $unsyncedCount = Message::where('thread_id', 0)->count();
        if ($unsyncedCount == 0) {
            Schema::table('messages', function (Blueprint $table) {
                $table->foreign('thread_id')->references('ID')->on('message_threads')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::rename('messages', 'messages_');
        Schema::rename('messages_', 'Messages');

        Schema::table('Messages', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeysFound = $sm->listTableForeignKeys('Messages');

            foreach ($foreignKeysFound as $foreignKey) {
                if ($foreignKey->getName() == 'messages_thread_id_foreign'
                    || $foreignKey->getName() == 'messages_author_id_foreign') {
                    $table->dropForeign($foreignKey->getName());
                }
            }

            $table->dropColumn('thread_id');
            $table->dropColumn('author_id');

            $table->renameColumn('body', 'Payload');
            $table->renameColumn('created_at', 'TimeSent');

            $table->renameColumn('id', 'ID');

            $table->unsignedBigInteger('recipient_id')->nullable()->after('ID');
            $table->unsignedBigInteger('sender_id')->nullable()->after('recipient_id');

            $table->timestampTz('read_at')->nullable()->before('Unread');

            $table->unsignedInteger('Type')->nullable()->after('Unread');

            $table->timestamp('recipient_deleted_at')->nullable();
            $table->timestamp('sender_deleted_at')->nullable();

            $table->softDeletesTz();

            $table->index('read_at');

            $table->foreign('recipient_id', 'messages_recipient_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('sender_id', 'messages_sender_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }
};
