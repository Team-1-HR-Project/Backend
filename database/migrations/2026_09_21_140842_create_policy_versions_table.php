<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('policy_id')
                ->constrained('policies')
                ->cascadeOnDelete();

            $table->unsignedInteger('version');

            $table->longText('content');

            $table->enum('status', [
                'draft',
                'active',
                'archived',
            ])->default('draft');

            $table->date('effective_date')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['policy_id', 'version']);

            $table->index('status');
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
