<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/notification_handler.php'; // To notify patient

header('Content-Type: application/json');
session_start();

// 1. Security: Only Doctors or Admin can update via API
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Input Parsing (Supports JSON payload)
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['appointment_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$appt_id = $data['appointment_id'];
$status = $data['status'];
$valid_statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // 3. Update Status
    $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $result = $stmt->execute([$status, $appt_id]);

    if ($result) {
        // 4. Send Notification to Patient
        // Fetch patient user_id
        $p_stmt = $db->prepare("
            SELECT p.user_id 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = ?
        ");
        $p_stmt->execute([$appt_id]);
        $patient = $p_stmt->fetch();

        if ($patient) {
            $msg = "Your appointment status has been updated to: " . $status;
            // Assuming NotificationHandler class exists from previous steps
            if (class_exists('NotificationHandler')) {
                NotificationHandler::create($patient['user_id'], $msg, 'info');
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Appointment updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>