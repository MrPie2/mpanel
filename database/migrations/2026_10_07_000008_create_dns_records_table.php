<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('name', 253);
            $table->text('value');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedSmallInteger('priority')->nullable();
            $table->timestamps();
            $table->index(['website_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
