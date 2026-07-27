<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'claimed_validity_days')) {
                $table->integer('claimed_validity_days')->nullable()->after('expires_at')->comment('Days after claiming before coupon expires if not used');
            }
        });

        Schema::table('claimed_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('claimed_coupons', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'claimed_validity_days')) $table->dropColumn('claimed_validity_days');
        });
        Schema::table('claimed_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('claimed_coupons', 'expires_at')) $table->dropColumn('expires_at');
        });
    }
};
