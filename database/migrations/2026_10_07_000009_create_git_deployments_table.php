<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('git_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('repository_url', 500);
            $table->string('branch', 255)->default('main');
            $table->string('deploy_path', 500);
            $table->string('webhook_secret_hash', 255)->nullable();
            $table->string('status', 30)->default('disconnected')->index();
            $table->string('last_commit', 100)->nullable();
            $table->timestamp('last_deployed_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique('website_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_deployments');
    }
};