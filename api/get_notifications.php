<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// 1. Check Auth
if (!isset($_SESSION['user_id'])) {
    // Return empty array if not logged in, rather than error, to stop polling noise
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

try {
    // 2. Fetch Unread Notifications
    $stmt = $db->prepare("
        SELECT notification_id, message, type, created_at 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // 3. Return Data
    echo json_encode([
        'count' => count($notifications),
        'data' => $notifications
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>