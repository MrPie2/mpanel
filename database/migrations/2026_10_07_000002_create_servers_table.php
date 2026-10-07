<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->ipAddress('ip_address');
            $table->unsignedSmallInteger('port')->default(8443);
            $table->string('agent_token_hash')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->decimal('cpu_percent',5,2)->nullable();
            $table->decimal('memory_percent',5,2)->nullable();
            $table->decimal('disk_percent',5,2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id','status']);
        });
    }

    public function down(): void { Schema::dropIfExists('servers'); }
};
