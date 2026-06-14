<?php
require_once __DIR__ . '/../../includes/db_connect.php';

$stmt = $pdo->query("SELECT name, type, status FROM em_event_destinations WHERE name = 'Internal Queue'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Internal Queue Status Check:\n";
echo "============================\n";
echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
