<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }
                if (!Schema::hasColumn('users', 'deleted_by')) {
                    $table->string('deleted_by', 150)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'deleted_at')) {
                    $table->dropColumn('deleted_at');
                }
                if (Schema::hasColumn('users', 'deleted_by')) {
                    $table->dropColumn('deleted_by');
                }
            });
        }
    }
};
