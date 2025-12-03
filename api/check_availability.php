<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// 1. Validate Input
if (!isset($_GET['doctor_id']) || !isset($_GET['date']) || !isset($_GET['time'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$doctor_id = $_GET['doctor_id'];
$date = $_GET['date'];
$time = $_GET['time'];

$db = Database::getInstance()->getConnection();

try {
    // 2. Check if the slot is already booked
    // We check for any appointment with the same doctor, date, and time that isn't cancelled
    $stmt = $db->prepare("
        SELECT appointment_id 
        FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND appointment_time = ? 
        AND status != 'Cancelled'
    ");
    $stmt->execute([$doctor_id, $date, $time]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['available' => false, 'message' => 'Slot already booked']);
    } else {
        // Optional: Double check if doctor is actually working that day (Validation layer)
        $dayOfWeek = date('l', strtotime($date));
        $checkSchedule = $db->prepare("
            SELECT availability_id 
            FROM doctor_availability 
            WHERE doctor_id = ? 
            AND day_of_week = ? 
            AND is_active = 1
        ");
        $checkSchedule->execute([$doctor_id, $dayOfWeek]);

        if ($checkSchedule->rowCount() > 0) {
            echo json_encode(['available' => true, 'message' => 'Slot is available']);
        } else {
            echo json_encode(['available' => false, 'message' => 'Doctor is not available on this day']);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>