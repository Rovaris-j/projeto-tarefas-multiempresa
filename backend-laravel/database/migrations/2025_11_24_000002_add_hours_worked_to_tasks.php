<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campo de horas trabalhadas na tabela de tarefas
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'hours_worked')) {
                $table->decimal('hours_worked', 5, 2)->default(0)->after('progress');
            }
        });
    }

    /**
     * Remove o campo de horas trabalhadas
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'hours_worked')) {
                $table->dropColumn('hours_worked');
            }
        });
    }
};

