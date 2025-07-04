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
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('subtotal')->nullable();
            $table->string('descuento')->nullable();
            $table->string('total');
            $table->string('moneda')->nullable();
            $table->string('folio');
            $table->string('fecha_inicial');
            $table->string('fecha_final');
            $table->string('dias_pagados');
            $table->integer('employee_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
