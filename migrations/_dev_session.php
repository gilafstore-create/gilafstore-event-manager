<?php
// TEMPORARY LOCAL DEV ONLY — delete after screenshots
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    http_response_code(403); exit('forbidden');
}
session_start();
$_SESSION['admin'] = ['id' => 4, 'name' => 'Gilaf Store Admin', 'email' => 'gilafstore@gmail.com', 'is_admin' => 1];
$target = $_GET['to'] ?? '/gilafstore.com/public_html/event-manager/pages/event-operations/logs.php';
header('Location: ' . $target);
exit;
