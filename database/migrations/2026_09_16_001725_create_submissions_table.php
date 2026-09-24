<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create submissions table.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('note')->nullable();

            $table->string('status')
                ->default('Pending Review');

            $table->timestamp('submitted_at')
                ->useCurrent();

            $table->timestamps();

            $table->index(['task_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Drop submissions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
