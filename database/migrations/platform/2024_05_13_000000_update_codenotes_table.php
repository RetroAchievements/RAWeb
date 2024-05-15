<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('CodeNotes', 'memory_notes');

        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('Address', 'address');
            $table->renameColumn('Note', 'body');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('address', 'Address');
            $table->renameColumn('body', 'Note');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        Schema::rename('memory_notes', 'CodeNotes');
    }
};
