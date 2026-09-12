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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('company_location_id')
                ->nullable()
                ->constrained('company_locations')
                ->nullOnDelete();

            $table->date('date');

            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();

            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();

            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();

            $table->enum('status', ['Present', 'Late', 'Absent'])->default('Present');

            $table->unsignedInteger('worked_seconds')->default(0);

            $table->boolean('is_exception')->default(false);
            $table->string('exception_reason')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'date']);

            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
