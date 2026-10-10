<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            // Terminal stays unavailable until a server-side isolation check succeeds.
            $table->string('terminal_linux_user', 32)->nullable()->after('status');
            $table->string('terminal_isolation_status', 24)->default('pending')->index()->after('terminal_linux_user');
            $table->timestamp('terminal_ready_at')->nullable()->after('terminal_isolation_status');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropIndex(['terminal_isolation_status']);
            $table->dropColumn(['terminal_linux_user', 'terminal_isolation_status', 'terminal_ready_at']);
        });
    }
};
