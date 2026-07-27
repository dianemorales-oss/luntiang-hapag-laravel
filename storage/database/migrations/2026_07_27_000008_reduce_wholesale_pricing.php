<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reduce wholesale price of every wholesale product by ₱400 while preserving logic
        // Identify wholesale products by category slug wholesale, or name contains Wholesale, or unit contains 50 cups / per tray / wholesale
        $wholesaleCategoryIds = DB::table('categories')->where('slug', 'wholesale')->pluck('id');

        DB::table('products')
            ->where(function($q) use ($wholesaleCategoryIds) {
                $q->whereIn('category_id', $wholesaleCategoryIds)
                  ->orWhere('name', 'like', '%Wholesale%')
                  ->orWhere('name', 'like', '%50-Cup%')
                  ->orWhere('name', 'like', '%Tray%')
                  ->orWhere('unit', 'like', '%50 cups%')
                  ->orWhere('unit', 'like', '%per tray%')
                  ->orWhere('unit', 'like', '%wholesale%');
            })
            ->where('price', '>=', 400)
            ->decrement('price', 400);
    }

    public function down(): void
    {
        // Revert reduction
        $wholesaleCategoryIds = DB::table('categories')->where('slug', 'wholesale')->pluck('id');
        DB::table('products')
            ->where(function($q) use ($wholesaleCategoryIds) {
                $q->whereIn('category_id', $wholesaleCategoryIds)
                  ->orWhere('name', 'like', '%Wholesale%')
                  ->orWhere('name', 'like', '%50-Cup%')
                  ->orWhere('name', 'like', '%Tray%')
                  ->orWhere('unit', 'like', '%50 cups%')
                  ->orWhere('unit', 'like', '%per tray%');
            })
            ->increment('price', 400);
    }
};
