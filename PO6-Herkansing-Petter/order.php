<?php
session_start();
require_once 'dbconnect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['cart']) || !is_array($data['cart']) || count($data['cart']) === 0) {
    echo json_encode(['success' => false, 'message' => 'Winkelmandje is leeg.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_data = json_encode($data['cart']);
$total = 0;
foreach ($data['cart'] as $item) {
    $total += isset($item['price']) ? floatval($item['price']) : 0;
}

try {
    $stmt = $db->prepare('INSERT INTO orders (user_id, order_data, total, order_date) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, $order_data, $total]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Databasefout: ' . $e->getMessage()]);
} 