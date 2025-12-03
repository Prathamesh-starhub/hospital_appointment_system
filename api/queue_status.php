<?php
require_once '../config/database.php';
require_once '../includes/queue_handler.php'; // Reusing the logic we wrote earlier

header('Content-Type: application/json');

// Input: Appointment ID (Usually passed from a patient's "Live Status" page)
if (!isset($_GET['appt_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Appointment ID required']);
    exit;
}

$appt_id = $_GET['appt_id'];

try {
    // Check if QueueHandler class exists (It should based on previous steps)
    if (class_exists('QueueHandler')) {
        
        $status = QueueHandler::getStatus($appt_id);

        if ($status) {
            echo json_encode([
                'success' => true,
                'queue_number' => $status['queue_number'],
                'current_status' => $status['status'],
                'people_ahead' => $status['people_ahead'],
                'estimated_wait_mins' => $status['estimated_wait']
            ]);
        } else {
            // Not in queue yet
            echo json_encode([
                'success' => false,
                'message' => 'Not checked in yet'
            ]);
        }

    } else {
        // Fallback if Handler is missing (Raw SQL)
        $db = Database::getInstance()->getConnection();
        
        // Get My Queue Info
        $stmt = $db->prepare("SELECT queue_number, status, check_in_time FROM appointment_queue WHERE appointment_id = ?");
        $stmt->execute([$appt_id]);
        $myQ = $stmt->fetch();

        if ($myQ) {
            echo json_encode(['success' => true, 'current_status' => $myQ['status'], 'queue_number' => $myQ['queue_number']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not in queue']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>