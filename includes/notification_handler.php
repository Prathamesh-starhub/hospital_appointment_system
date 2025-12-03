<?php
require_once __DIR__ . '/../config/database.php';

class NotificationHandler {

    /**
     * Create a new notification
     * @param int $userId Receiver ID
     * @param string $message content
     * @param string $type 'general', 'alert', 'success'
     */
    public static function create($userId, $message, $type = 'general') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $message, $type]);
    }

    /**
     * Get unread notifications for a user
     */
    public static function getUnread($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark a specific notification as read
     */
    public static function markAsRead($notificationId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
        return $stmt->execute([$notificationId]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
?>