<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('pairing_token_hash')->nullable()->after('agent_token_hash');
            $table->timestamp('pairing_token_expires_at')->nullable()->after('pairing_token_hash')->index();
            $table->timestamp('agent_paired_at')->nullable()->after('pairing_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['pairing_token_hash','pairing_token_expires_at','agent_paired_at']);
        });
    }
};
