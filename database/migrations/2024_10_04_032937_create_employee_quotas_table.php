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
        Schema::create('employee_quotas', function (Blueprint $table) {
            $table->id();
            $table->integer('period');
            $table->integer('year');
            $table->integer('employee_id');
            $table->integer('days');
            $table->double('base_salary');
            $table->integer('absence');
            $table->integer('incapacity');
            $table->integer('total_days');
            $table->integer('difference_days');
            $table->double('base_price_em');
            $table->double('base_price_rt');
            $table->double('base_price_iv');
            $table->double('fixed_price');
            $table->double('sdmg');
            $table->double('in_cash');
            $table->double('disability_health');
            $table->double('pensioners');
            $table->double('risk_price');
            $table->double('nurseries');
            $table->double('total_audit');
            $table->double('total_company');
            $table->double('difference');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_quotas');
    }
};
