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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->integer('period');
            $table->integer('year');
            $table->integer('employee_id');
            $table->integer('category_id');
            $table->double('daily_salary');
            $table->double('daily_bonus')->default(0);
            $table->integer('vacations_days')->default(0);
            $table->double('vacations_import')->default(0);
            $table->double('vacation_bonus')->default(0);
            $table->double('sdi')->default(0);
            $table->double('sdi_variable')->default(0);
            $table->double('total_sdi')->default(0);
            $table->double('sdi_limit')->default(0);
            $table->double('sdi_aud')->default(0);
            $table->double('sdi_quoted')->default(0);
            $table->double('difference')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
