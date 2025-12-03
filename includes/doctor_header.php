<?php
// includes/doctor_header.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireRole('doctor');
$user_id = Auth::user();
$db = Database::getInstance()->getConnection();

// Get Doctor Profile based on User ID
$stmt = $db->prepare("SELECT * FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor profile not found. Please contact admin.");
}

$doctor_id = $doctor['doctor_id'];
?>