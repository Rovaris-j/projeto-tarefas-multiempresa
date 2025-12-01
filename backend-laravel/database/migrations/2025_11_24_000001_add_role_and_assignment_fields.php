<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('admin')->after('password');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('tasks', 'progress')) {
                $table->unsignedTinyInteger('progress')->default(0)->after('priority');
            }
        });

        if (Schema::hasColumn('tasks', 'assigned_to')) {
            DB::table('tasks')->update([
                'assigned_to' => DB::raw('COALESCE(assigned_to, user_id)')
            ]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'assigned_to')) {
                $table->foreign('assigned_to')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
            if (Schema::hasColumn('tasks', 'progress')) {
                $table->dropColumn('progress');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};

