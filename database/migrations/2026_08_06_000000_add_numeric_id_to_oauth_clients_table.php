<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('numeric_id')->nullable()->unique()->after('id');
        });

        $nextNumericId = 1;
        DB::table('oauth_clients')
            ->select('id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function ($client) use (&$nextNumericId) {
                DB::table('oauth_clients')
                    ->where('id', $client->id)
                    ->update(['numeric_id' => $nextNumericId]);

                $nextNumericId++;
            });

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('numeric_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('numeric_id');
        });
    }
};
