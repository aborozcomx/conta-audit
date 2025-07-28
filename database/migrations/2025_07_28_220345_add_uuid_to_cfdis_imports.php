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
        Schema::table('cfdi_imports', function (Blueprint $table) {
            $table->string('uuid'); // Aquí se guarda la fila del Excel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_imports', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
