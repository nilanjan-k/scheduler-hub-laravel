<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduler_hub_runs', function (Blueprint $table) {
            $table->id();

            // Stable hash from TaskIdentifier, not an array index.
            $table->string('task_id', 16)->index();

            $table->string('command');
            $table->string('type', 20);
            $table->string('description')->nullable();

            $table->enum('status', ['running', 'success', 'failed', 'skipped'])->default('running');
            $table->enum('trigger', ['scheduled', 'manual'])->default('scheduled');

            $table->longText('output')->nullable();
            $table->text('error')->nullable();

            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->string('triggered_by_ip', 45)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index(['task_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_hub_runs');
    }
};
