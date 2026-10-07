<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->boolean('ssl_enabled')->default(false)->after('status');
            $table->timestamp('ssl_issued_at')->nullable()->after('ssl_enabled');
            $table->timestamp('ssl_expires_at')->nullable()->after('ssl_issued_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropIndex(['ssl_expires_at']);
            $table->dropColumn(['ssl_enabled','ssl_issued_at','ssl_expires_at']);
        });
    }
};
