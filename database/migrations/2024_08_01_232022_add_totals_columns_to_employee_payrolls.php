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
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->string('total_deduction');
            $table->string('total_others');
            $table->string('total_perception');
            $table->string('total_salary');
            $table->integer('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropColumn('total_deduction');
            $table->dropColumn('total_others');
            $table->dropColumn('total_perception');
            $table->dropColumn('total_salary');
            $table->dropColumn('period');
        });
    }
};
