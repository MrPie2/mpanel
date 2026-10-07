<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->string('status')->default('queued')->index();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['server_id','status']);
        });
    }

    public function down(): void { Schema::dropIfExists('agent_jobs'); }
};
