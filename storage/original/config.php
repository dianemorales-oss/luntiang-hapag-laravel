<?php
/**
 * config.php
 * ------------------------------------------------------------------
 * Bootstraps the database connection for every page in the project.
 *
 * All connection settings and table-creation logic now live in a
 * single place: db.php. That file creates the database and tables
 * if they do not exist yet (safe to run on every request) and hands
 * back a ready-to-use $conn (PDO) object.
 *
 * Keeping the credentials/schema in one place (db.php) instead of
 * duplicating them here avoids the two files drifting out of sync.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/database/db.php';

// Cart persistence helpers (sync session ↔ DB for logged-in users)
require_once __DIR__ . '/includes/cart-helpers.php';

// $conn (PDO) is now available to any file that does require 'config.php';
