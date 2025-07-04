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
        Schema::table('employee_payroll_concepts', function (Blueprint $table) {
            $table->integer('period');
            $table->integer('year');
            $table->integer('employee_id');
            $table->boolean('is_exented')->default(false);
            $table->boolean('is_taxed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payroll_concepts', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->dropColumn('year');
            $table->dropColumn('employee_id');
            $table->dropColumn('is_exented');
            $table->dropColumn('is_taxed');
        });
    }
};
