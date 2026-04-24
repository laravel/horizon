<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('horizon_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('connection')->nullable();
            $table->string('queue')->nullable();
            $table->string('name')->nullable();
            $table->string('status', 16)->nullable();
            $table->longText('payload');
            $table->longText('exception')->nullable();
            $table->longText('context')->nullable();
            $table->timestamp('failed_at', 6)->nullable();
            $table->timestamp('completed_at', 6)->nullable();
            $table->longText('retried_by')->nullable();
            $table->timestamp('reserved_at', 6)->nullable();
            $table->unsignedInteger('delay')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps(6);

            $table->index(['status', 'queue']);
            $table->index('expires_at');
        });

        Schema::create('horizon_job_references', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 32);
            $table->uuid('job_id');
            $table->string('queue')->nullable();
            $table->bigInteger('score');
            $table->timestamps();

            $table->unique(['type', 'job_id']);
            $table->index(['type', 'score', 'id']);
            $table->index(['type', 'queue']);
        });

        Schema::create('horizon_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag');
            $table->uuid('job_id');
            $table->bigInteger('score');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['tag', 'job_id']);
            $table->index(['tag', 'score']);
            $table->index('expires_at');
        });

        Schema::create('horizon_monitored_tags', function (Blueprint $table) {
            $table->string('tag')->primary();
            $table->timestamps();
        });

        Schema::create('horizon_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->string('kind', 8);
            $table->unsignedBigInteger('throughput')->default(0);
            $table->double('runtime')->default(0);
            $table->timestamps();

            $table->unique('key');
            $table->index('kind');
        });

        Schema::create('horizon_metric_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->string('kind', 8);
            $table->unsignedBigInteger('throughput')->default(0);
            $table->double('runtime')->default(0);
            $table->double('wait')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['key', 'recorded_at']);
        });

        Schema::create('horizon_metric_increments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->string('kind', 8);
            $table->double('runtime')->nullable();
            $table->timestamp('recorded_at', 6);

            $table->index(['key', 'id']);
        });

        Schema::create('horizon_states', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('horizon_master_supervisors', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('environment')->nullable();
            $table->unsignedInteger('pid')->nullable();
            $table->string('status', 16)->nullable();
            $table->longText('supervisors')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });

        Schema::create('horizon_supervisors', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('master');
            $table->unsignedInteger('pid')->nullable();
            $table->string('status', 16)->nullable();
            $table->longText('processes')->nullable();
            $table->longText('options')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('master');
            $table->index('expires_at');
        });

        Schema::create('horizon_processes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('master');
            $table->string('process_id', 64);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['master', 'process_id']);
            $table->index('master');
        });

        Schema::create('horizon_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('horizon_commands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue');
            $table->string('command');
            $table->longText('options')->nullable();
            $table->timestamps();

            $table->index(['queue', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horizon_commands');
        Schema::dropIfExists('horizon_locks');
        Schema::dropIfExists('horizon_processes');
        Schema::dropIfExists('horizon_supervisors');
        Schema::dropIfExists('horizon_master_supervisors');
        Schema::dropIfExists('horizon_states');
        Schema::dropIfExists('horizon_metric_increments');
        Schema::dropIfExists('horizon_metric_snapshots');
        Schema::dropIfExists('horizon_metrics');
        Schema::dropIfExists('horizon_monitored_tags');
        Schema::dropIfExists('horizon_tags');
        Schema::dropIfExists('horizon_job_references');
        Schema::dropIfExists('horizon_jobs');
    }
};
