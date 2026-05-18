<?php
session_start();
require_once('../config/db.php');
require_once('../includes/log_action.php');

logAction($pdo, 'Logout', 'Admin logged out.');

session_destroy();
header("Location: signin_admin.php");
exit();
?>