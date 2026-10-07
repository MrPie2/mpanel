<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('domain',253);
            $table->string('document_root',500);
            $table->string('php_version',20)->default('8.3');
            $table->string('status')->default('provisioning')->index();
            $table->timestamps();
            $table->unique(['server_id','domain']);
        });
    }

    public function down(): void { Schema::dropIfExists('websites'); }
};
