<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', config('activitylog.table_name') . '_subject_index');
            $table->nullableMorphs('causer', config('activitylog.table_name') . '_causer_index');
            $table->json('properties')->nullable();
            $table->timestampsTz();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
};
