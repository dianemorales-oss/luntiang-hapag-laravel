<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('admins', 'last_name')) {
                $table->string('last_name', 100)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('admins', 'profile_picture')) {
                $table->string('profile_picture', 255)->nullable()->after('role');
            }
        });

        // Backfill first_name, last_name from name if empty
        $admins = \App\Models\Admin::all();
        foreach ($admins as $admin) {
            if (empty($admin->first_name) && !empty($admin->name)) {
                $parts = explode(' ', $admin->name, 2);
                $admin->first_name = $parts[0] ?? $admin->name;
                $admin->last_name = $parts[1] ?? '';
                $admin->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'first_name')) $table->dropColumn('first_name');
            if (Schema::hasColumn('admins', 'last_name')) $table->dropColumn('last_name');
            if (Schema::hasColumn('admins', 'profile_picture')) $table->dropColumn('profile_picture');
        });
    }
};
