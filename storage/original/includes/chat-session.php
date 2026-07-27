<?php
/**
 * includes/chat-session.php
 * ------------------------------------------------------------------
 * Include this (after session_start() + config.php) on every page or
 * AJAX endpoint that reads/writes live_chat_messages for the current
 * visitor: live-chat.php, chat-send.php, chat-poll.php.
 *
 * Logged-in customers always get their permanent, account-based
 * chat_key (user-<id>), so their conversation survives logging out
 * and back in again — not just the current PHP session. Guests get
 * a random key that the client hands back to us on every request
 * (see below) rather than one stored in $_SESSION.
 *
 * Why not $_SESSION for guests: a PHP session cookie is shared by
 * every tab in the browser and lives until the whole browser closes,
 * so a guest who closes just one tab would still see the same chat
 * in a new tab. Instead the guest key is kept in that tab's
 * sessionStorage (see the inline script in live-chat.php), which is
 * scoped to the single tab: it survives a reload of that tab but is
 * gone the moment the tab itself is closed — exactly the behavior we
 * want. The client sends it back as ?gk=... (or a 'gk' POST/JSON
 * field for the AJAX endpoints); if it's missing or malformed we
 * simply mint a fresh one, which is also what naturally happens the
 * first time a brand-new tab hits the page.
 *
 * Guest and customer conversations are intentionally kept completely
 * separate: a guest's chat_key is never reused, migrated, or
 * attached to an account after login. Any guest chat_key the client
 * still has stashed from before login is simply never sent as
 * chat_key again once logged in — the guest conversation stays
 * exactly where it was, owned only by that guest key, and is never
 * shown to the logged-in customer.
 *
 * Sets: $chatKey, $customerName, $userId
 * ------------------------------------------------------------------
 */

if (isset($_SESSION['user_id'])) {

    $userId = $_SESSION['user_id'];
    $customerName = trim($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
    $chatKey = 'user-' . $userId;

} else {

    $raw = $_GET['gk'] ?? $_POST['gk'] ?? null;
    if (is_string($raw) && preg_match('/^[a-f0-9]{32}$/', $raw)) {
        $chatKey = $raw;
    } else {
        $chatKey = bin2hex(random_bytes(16));
    }
    $customerName = 'Guest ' . strtoupper(substr($chatKey, 0, 4));
    $userId = null;

}