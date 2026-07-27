<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'stock_product_id')) {
                $table->unsignedBigInteger('stock_product_id')->nullable()->after('plants_available')->index();
            }
            if (!Schema::hasColumn('products', 'stock_multiplier')) {
                $table->unsignedInteger('stock_multiplier')->default(1)->after('stock_product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'stock_multiplier')) {
                $table->dropColumn('stock_multiplier');
            }
            if (Schema::hasColumn('products', 'stock_product_id')) {
                $table->dropColumn('stock_product_id');
            }
        });
    }
};
