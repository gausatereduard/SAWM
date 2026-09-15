<?php
session_start();
require_once __DIR__ . '/helper/auth.helper.php';

logout();
header('Location: index.php');
exit;
?>
