<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (!$doctor_id || !$date) {
    echo json_encode([]); // Return empty if params missing
    exit;
}

$db = Database::getInstance()->getConnection();
$dayOfWeek = date('l', strtotime($date)); // e.g., "Monday"

// 1. Get Doctor's Schedule for that day
$stmt = $db->prepare("SELECT start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1");
$stmt->execute([$doctor_id, $dayOfWeek]);
$schedule = $stmt->fetch();

if (!$schedule) {
    echo json_encode([]); // Doctor not working/available on this day
    exit;
}

// 2. Get Booked Appointments for that day
$stmt2 = $db->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'Cancelled'");
$stmt2->execute([$doctor_id, $date]);
$booked_times = $stmt2->fetchAll(PDO::FETCH_COLUMN);

// 3. Generate 30-minute slots
$slots = [];
$start = strtotime($schedule['start_time']);
$end = strtotime($schedule['end_time']);
$interval = 30 * 60; // 30 minutes in seconds

for ($time = $start; $time < $end; $time += $interval) {
    $slot_time = date('H:i:00', $time);
    $display_time = date('h:i A', $time);
    
    // Check if slot is booked
    $is_booked = in_array($slot_time, $booked_times);
    
    // Check if slot is in the past (if booking for today)
    $is_past = ($date == date('Y-m-d') && $time < time());

    if (!$is_booked && !$is_past) {
        $slots[] = [
            'value' => $slot_time,
            'label' => $display_time
        ];
    }
}

echo json_encode($slots);
?>