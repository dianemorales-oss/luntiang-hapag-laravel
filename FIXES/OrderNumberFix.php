<?php
// Improved order number generation – replace in CheckoutController@store

use Illuminate\Support\Str;

// Option 1: ULID style (recommended)
$orderNumber = 'LH-' . strtoupper(Str::ulid()); // LH-01H...\n// Truncate for readability: use 8 random chars
$orderNumber = 'LH-' . strtoupper(Str::random(8)); // e.g. LH-A3B9K2L1
while (Order::where('order_number', $orderNumber)->exists()) {
    $orderNumber = 'LH-' . strtoupper(Str::random(8));
}

// Option 2: Time + random
// $orderNumber = 'LH-' . date('ymd') . '-' . str_pad(random_int(0,9999),4,'0',STR_PAD_LEFT);

// Option 3: Use auto-increment padded
// $nextId = (Order::max('id') ?? 0) + 1;
// $orderNumber = 'LH-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
