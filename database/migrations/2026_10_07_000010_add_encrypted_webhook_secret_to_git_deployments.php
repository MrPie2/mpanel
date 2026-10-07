<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('git_deployments', function (Blueprint $table) {
            $table->text('webhook_secret_encrypted')->nullable()->after('webhook_secret_hash');
        });
    }

    public function down(): void
    {
        Schema::table('git_deployments', function (Blueprint $table) {
            $table->dropColumn('webhook_secret_encrypted');
        });
    }
};