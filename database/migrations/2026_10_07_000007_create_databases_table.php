<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('username', 32);
            $table->text('password_encrypted')->nullable();
            $table->string('host', 255)->default('127.0.0.1');
            $table->unsignedSmallInteger('port')->default(3306);
            $table->string('status')->default('provisioning')->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['server_id', 'name']);
            $table->unique(['server_id', 'username']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
