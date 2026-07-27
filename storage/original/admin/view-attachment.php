<?php
/**
 * admin/view-attachment.php
 * ------------------------------------------------------------------
 * Serves a customer-uploaded file (ticket attachment, proof of
 * purchase, or damage photo) to a logged-in admin.
 *
 * Fixes the previous "View Attachment" links, which pointed straight
 * at "../<path>" and could 404 depending on how/where the app was
 * deployed. Routing through this script instead means the file is
 * always resolved from a known, fixed location on disk (relative to
 * this file) regardless of the site's URL structure, and we can
 * validate the path so only files inside uploads/ are ever served.
 * ------------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$relPath = str_replace('\\', '/', $_GET['path'] ?? '');

// Only allow serving files that live directly under one of the known
// uploads subfolders, with a safe filename — blocks path traversal
// ("..") and stops this from being used as a generic file reader.
$isValidPath = $relPath !== ''
    && !str_contains($relPath, '..')
    && preg_match('#^uploads/(tickets|warranty|returns)/[A-Za-z0-9_-]+\.(jpg|jpeg|png|pdf)$#i', $relPath);

if (!$isValidPath) {
    http_response_code(404);
    exit('File not found.');
}

$uploadsRoot = realpath(__DIR__ . '/../uploads');
$fullPath = realpath(__DIR__ . '/../' . $relPath);

if (
    $uploadsRoot === false
    || $fullPath === false
    || !str_starts_with($fullPath, $uploadsRoot)
    || !is_file($fullPath)
) {
    http_response_code(404);
    exit('File not found.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'pdf'  => 'application/pdf',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit();
