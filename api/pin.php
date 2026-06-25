<?php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$symbol = strtoupper(trim($body['symbol'] ?? ''));
$name   = trim($body['name'] ?? '');
$action = $body['action'] ?? '';
$uid    = current_user_id();

if (!$symbol || !in_array($action, ['pin', 'unpin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$db = get_db();


$stmt = $db->prepare('INSERT IGNORE INTO stocks (symbol, name) VALUES (?, ?)');
$stmt->execute([$symbol, $name ?: $symbol]);


$stmt = $db->prepare('SELECT id FROM stocks WHERE symbol = ?');
$stmt->execute([$symbol]);
$stock = $stmt->fetch();

if (!$stock) {
    echo json_encode(['error' => 'Stock not found']);
    exit;
}

$stock_id = $stock['id'];

if ($action === 'pin') {
    $db->prepare('INSERT IGNORE INTO stock_user (user_id, stock_id) VALUES (?, ?)')
       ->execute([$uid, $stock_id]);
    echo json_encode(['pinned' => true]);
} else {
    $db->prepare('DELETE FROM stock_user WHERE user_id = ? AND stock_id = ?')
       ->execute([$uid, $stock_id]);
    echo json_encode(['pinned' => false]);
}
