<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claimed_coupons') && !Schema::hasColumn('claimed_coupons', 'used_at')) {
            Schema::table('claimed_coupons', function (Blueprint $table) {
                $table->timestamp('used_at')->nullable()->after('claimed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('claimed_coupons') && Schema::hasColumn('claimed_coupons', 'used_at')) {
            Schema::table('claimed_coupons', function (Blueprint $table) {
                $table->dropColumn('used_at');
            });
        }
    }
};
