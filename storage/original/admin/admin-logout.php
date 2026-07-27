<?php
session_start();

// Only clear admin-related session keys, so an admin logging out
// doesn't accidentally log out a customer session on the same
// browser (both use the same PHP session).
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);

header("Location: admin-login.php");
exit();
