<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_events', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->date('start_date');

            $table->date('end_date');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_events');
    }
};
