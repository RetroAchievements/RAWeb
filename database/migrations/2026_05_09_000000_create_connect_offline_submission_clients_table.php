<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('connect_offline_submission_clients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('client')->unique();
            $table->timestamps();
        });

        DB::table('connect_offline_submission_clients')->insert([
            'client' => 'RAOfflineProxy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('connect_offline_submission_clients');
    }
};
