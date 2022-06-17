<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    protected Builder $schema;

    public function __construct()
    {
        $this->schema = Schema::connection($this->getConnection());
    }

    public function up(): void
    {
        $this->schema->create('oauth_access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('client_id');
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->timestampsTz();
            $table->dateTime('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('oauth_access_tokens');
    }

    public function getConnection(): ?string
    {
        return config('passport.storage.database.connection');
    }
};
