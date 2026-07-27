<?php
/**
 * includes/restrict-customer.php
 * ------------------------------------------------------------------
 * This file previously blocked logged-in customers from browsing
 * the public pages (home, products, about). That made no sense for
 * an e-commerce platform — customers need to SHOP.
 *
 * Now this file is a harmless no-op: all pages are open to everyone.
 * Login is only enforced on pages that require authentication
 * (checkout, order tracking, my-profile, submit-ticket, etc),
 * which handle login checks themselves via session checks at the
 * top of the file, not this include.
 * ------------------------------------------------------------------
 */
// This file intentionally left empty — all auth gating is now
// handled per-page.
