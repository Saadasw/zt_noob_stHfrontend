<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

header('Content-Type: application/json');

if (!isset($_GET['doctor_id'])) {
    echo json_encode(['error' => 'No doctor ID provided']);
    exit;
}

$doctor_id = $_GET['doctor_id'];

try {
    // Fetch weekly schedule
    $stmt = $pdo->prepare("SELECT * FROM doctor_weekly_schedules WHERE doctor_id = ? AND is_active = 1");
    $stmt->execute([$doctor_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch slot duration
    $stmt = $pdo->prepare("SELECT slot_duration FROM doctor_profiles WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'schedule' => $schedule,
        'slot_duration' => $profile['slot_duration'] ?? 30
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
