<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['specialization'])) {
    echo json_encode([]);
    exit;
}

$spec = $_GET['specialization'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT doctor_id, full_name, consultation_fee FROM doctors WHERE specialization = ? AND status = 'active'");
$stmt->execute([$spec]);
$doctors = $stmt->fetchAll();

echo json_encode($doctors);
?>