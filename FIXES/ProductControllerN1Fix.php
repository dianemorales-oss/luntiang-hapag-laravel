<?php
// Fix N+1 in ProductController@index – replace map loop with eager loading

$query = Product::where('is_active', 1)
    ->withCount(['reviews as review_count' => fn($q) => $q->where('is_approved', 1)])
    ->withAvg(['reviews as avg_rating' => fn($q) => $q->where('is_approved', 1)], 'rating');

// Then no need for map:
$products = $query->get();

// If you need custom accessor, ensure casting:
$products->each(fn($p) => $p->avg_rating = round($p->avg_rating ?? 0, 1));
