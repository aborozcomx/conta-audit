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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('rfc');
            $table->string('curp');
            $table->string('age')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('number');
            $table->string('social_number')->nullable();
            $table->string('depto');
            $table->double('base_salary');
            $table->double('daily_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('rfc');
            $table->dropColumn('curp');
            $table->dropColumn('age');
            $table->dropColumn('start_date');
            $table->dropColumn('number');
            $table->dropColumn('social_number');
            $table->dropColumn('depto');
            $table->dropColumn('base_salary');
            $table->dropColumn('daily_salary');
        });
    }
};
