<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_id')
                ->constrained('policies')
                ->cascadeOnDelete();

            $table->foreignId('policy_version_id')
                ->nullable()
                ->constrained('policy_versions')
                ->nullOnDelete();

            $table->foreignId('performed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('action');

            $table->string('old_status')->nullable();

            $table->string('new_status')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_audits');
    }
};
