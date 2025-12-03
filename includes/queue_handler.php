<?php
require_once __DIR__ . '/../config/database.php';

class QueueHandler {

    /**
     * Add an appointment to the active queue (Check-In)
     */
    public static function addToQueue($appointmentId) {
        $db = Database::getInstance()->getConnection();
        
        // 1. Check if already in queue
        $check = $db->prepare("SELECT queue_id FROM appointment_queue WHERE appointment_id = ?");
        $check->execute([$appointmentId]);
        if ($check->rowCount() > 0) return false;

        // 2. Get next queue number for today
        // (This logic assumes a single global queue or requires doctor filtering. 
        //  Simple version: Auto-incrementing queue number)
        $stmt = $db->prepare("SELECT MAX(queue_number) as max_num FROM appointment_queue WHERE DATE(check_in_time) = CURDATE()");
        $stmt->execute();
        $row = $stmt->fetch();
        $nextNum = ($row['max_num'] ?? 0) + 1;

        // 3. Insert
        $ins = $db->prepare("INSERT INTO appointment_queue (appointment_id, queue_number, status) VALUES (?, ?, 'Waiting')");
        return $ins->execute([$appointmentId, $nextNum]);
    }

    /**
     * Get Queue Status for a specific Patient/Appointment
     */
    public static function getStatus($appointmentId) {
        $db = Database::getInstance()->getConnection();
        
        // Get current patient's queue details
        $stmt = $db->prepare("
            SELECT q.queue_number, q.status, a.doctor_id 
            FROM appointment_queue q
            JOIN appointments a ON q.appointment_id = a.appointment_id
            WHERE q.appointment_id = ?
        ");
        $stmt->execute([$appointmentId]);
        $myQueue = $stmt->fetch();

        if (!$myQueue) return null; // Not checked in yet

        // Calculate people ahead
        // Logic: Count people with status 'Waiting' who have a lower queue number for the SAME doctor
        $stmt2 = $db->prepare("
            SELECT COUNT(*) 
            FROM appointment_queue q
            JOIN appointments a ON q.appointment_id = a.appointment_id
            WHERE a.doctor_id = ? 
            AND q.status = 'Waiting' 
            AND q.queue_number < ?
            AND DATE(q.check_in_time) = CURDATE()
        ");
        $stmt2->execute([$myQueue['doctor_id'], $myQueue['queue_number']]);
        $peopleAhead = $stmt2->fetchColumn();

        return [
            'queue_number' => $myQueue['queue_number'],
            'status' => $myQueue['status'],
            'people_ahead' => $peopleAhead,
            'estimated_wait' => $peopleAhead * 15 // 15 mins per person
        ];
    }

    /**
     * Update Queue Status (Called by Doctor)
     * e.g., Waiting -> In Consultation -> Completed
     */
    public static function updateStatus($appointmentId, $status) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE appointment_queue SET status = ? WHERE appointment_id = ?");
        return $stmt->execute([$status, $appointmentId]);
    }
}
?>