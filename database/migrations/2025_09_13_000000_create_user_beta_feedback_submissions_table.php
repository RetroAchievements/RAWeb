<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_beta_feedback_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('beta_name', 100);
            $table->tinyInteger('rating'); // 1-5
            $table->text('positive_feedback')->nullable();
            $table->text('negative_feedback')->nullable();

            $table->string('page_url', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('visit_count')->nullable();
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_beta_feedback_submissions');
    }
};
