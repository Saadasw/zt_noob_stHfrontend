<?php
/**
 * Patient AI Chat API Endpoint
 * 
 * Handles chat requests from the Patient Portal.
 * Fetches patient-specific data, builds context, and calls Gemini API.
 * 
 * SEPARATE from api/ai_chat.php (admin-only) to maintain role isolation.
 */

session_start();

// JSON response headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config/db_connect.php';
require_once '../config/gemini_config.php';

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Send JSON response and exit
 */
function sendResponse($success, $data = null, $error = null, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'response' => $data,
        'error' => $error
    ]);
    exit;
}

/**
 * Validate patient session
 */
function validatePatientSession()
{
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Please log in to use the AI assistant.', 401);
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
        sendResponse(false, null, 'This feature is only available to patients.', 403);
    }
    return true;
}

/**
 * Get patient profile ID from user_id
 */
function getPatientProfileId($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/**
 * Fetch all available doctors with their weekly schedules
 */
function fetchDoctorData($pdo)
{
    $sql = "
        SELECT dp.id, u.name, dp.specialization, dp.department, dp.consultation_fee,
               b.name as branch_name,
               GROUP_CONCAT(
                 CONCAT(dws.day_of_week, ':', dws.start_time, '-', dws.end_time)
                 ORDER BY dws.day_of_week
                 SEPARATOR '; '
               ) as schedule
        FROM doctor_profiles dp
        JOIN users u ON dp.user_id = u.id
        JOIN branches b ON dp.branch_id = b.id
        LEFT JOIN doctor_weekly_schedules dws ON dws.doctor_id = dp.id AND dws.is_active = 1
        WHERE u.is_active = 1 AND dp.is_available = 1 AND dp.deleted_at IS NULL
        GROUP BY dp.id, u.name, dp.specialization, dp.department, dp.consultation_fee, b.name
    ";
    return $pdo->query($sql)->fetchAll();
}

/**
 * Fetch patient profile data (allergies, medical history, etc.)
 */
function fetchPatientProfile($pdo, $userId)
{
    $stmt = $pdo->prepare("
        SELECT pp.allergies, pp.medical_history, pp.blood_group, pp.date_of_birth, pp.gender,
               u.name as patient_name
        FROM patient_profiles pp
        JOIN users u ON pp.user_id = u.id
        WHERE pp.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Fetch recent appointments for the patient
 */
function fetchRecentAppointments($pdo, $patientProfileId)
{
    $stmt = $pdo->prepare("
        SELECT a.appointment_date, a.reason, a.status, u.name as doctor_name, dp.specialization
        FROM appointments a
        JOIN doctor_profiles dp ON a.doctor_id = dp.id
        JOIN users u ON dp.user_id = u.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$patientProfileId]);
    return $stmt->fetchAll();
}

/**
 * Fetch active prescriptions for the patient
 */
function fetchActivePrescriptions($pdo, $patientProfileId)
{
    $stmt = $pdo->prepare("
        SELECT p.prescription_no, p.created_at, p.status, ud.name as doctor_name,
               pi.medicine_name, pi.dosage, pi.frequency, pi.duration
        FROM prescriptions p
        JOIN prescription_items pi ON pi.prescription_id = p.id
        JOIN doctor_profiles dp ON p.doctor_id = dp.id
        JOIN users ud ON dp.user_id = ud.id
        WHERE p.patient_id = ? AND p.status IN ('active', 'partially_dispensed')
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$patientProfileId]);
    return $stmt->fetchAll();
}

/**
 * Fetch recent lab test results for the patient
 */
function fetchRecentLabTests($pdo, $patientProfileId)
{
    $stmt = $pdo->prepare("
        SELECT lt.test_no, ltt.name as test_name, lt.status, lt.result, lt.created_at, lt.completed_at
        FROM lab_tests lt
        JOIN lab_test_types ltt ON lt.test_type_id = ltt.id
        WHERE lt.patient_id = ?
        ORDER BY lt.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$patientProfileId]);
    return $stmt->fetchAll();
}

/**
 * Fetch recent medical records for the patient
 */
function fetchMedicalRecords($pdo, $patientProfileId)
{
    $stmt = $pdo->prepare("
        SELECT mr.chief_complaint, mr.diagnosis, mr.treatment_plan, mr.created_at,
               u.name as doctor_name
        FROM medical_records mr
        JOIN doctor_profiles dp ON mr.doctor_id = dp.id
        JOIN users u ON dp.user_id = u.id
        WHERE mr.patient_id = ? AND mr.deleted_at IS NULL
        ORDER BY mr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$patientProfileId]);
    return $stmt->fetchAll();
}

/**
 * Convert day_of_week number to day name
 */
function dayName($dayNum)
{
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return $days[$dayNum] ?? "Day $dayNum";
}

/**
 * Format doctor schedule string into readable text
 * Input: "1:09:00:00-17:00:00; 3:09:00:00-17:00:00"
 * Output: "Monday 09:00-17:00, Wednesday 09:00-17:00"
 */
function formatSchedule($scheduleStr)
{
    if (empty($scheduleStr)) {
        return 'Schedule not available';
    }

    $parts = explode('; ', $scheduleStr);
    $formatted = [];
    foreach ($parts as $part) {
        // Format: day_of_week:start_time-end_time
        $segments = explode(':', $part, 2);
        if (count($segments) === 2) {
            $dayNum = intval($segments[0]);
            $times = $segments[1];
            // Clean up time format (HH:MM:SS-HH:MM:SS -> HH:MM-HH:MM)
            $times = preg_replace('/:00(?=-|$)/', '', $times);
            $formatted[] = dayName($dayNum) . ' ' . $times;
        }
    }
    return implode(', ', $formatted) ?: 'Schedule not available';
}

/**
 * Build context string from all patient data
 */
function buildPatientContext($doctors, $profile, $appointments, $prescriptions, $labTests, $medicalRecords)
{
    $context = "";

    // Patient profile
    if ($profile) {
        $context .= "=== YOUR PATIENT PROFILE ===\n";
        $context .= "Name: " . ($profile['patient_name'] ?? 'N/A') . "\n";
        $context .= "Gender: " . ($profile['gender'] ?? 'N/A') . "\n";
        $context .= "Date of Birth: " . ($profile['date_of_birth'] ?? 'N/A') . "\n";
        $context .= "Blood Group: " . ($profile['blood_group'] ?? 'N/A') . "\n";
        $context .= "Allergies: " . ($profile['allergies'] ?? 'None recorded') . "\n";
        $context .= "Medical History: " . ($profile['medical_history'] ?? 'None recorded') . "\n\n";
    }

    // Available doctors
    if (!empty($doctors)) {
        $context .= "=== AVAILABLE DOCTORS ===\n";
        foreach ($doctors as $doc) {
            $context .= "- Dr. " . $doc['name'] . "\n";
            $context .= "  Specialization: " . ($doc['specialization'] ?? 'General') . "\n";
            $context .= "  Department: " . ($doc['department'] ?? 'N/A') . "\n";
            $context .= "  Consultation Fee: $" . number_format($doc['consultation_fee'], 2) . "\n";
            $context .= "  Branch: " . $doc['branch_name'] . "\n";
            $context .= "  Schedule: " . formatSchedule($doc['schedule']) . "\n\n";
        }
    }

    // Recent appointments
    if (!empty($appointments)) {
        $context .= "=== YOUR RECENT APPOINTMENTS (Last 5) ===\n";
        foreach ($appointments as $apt) {
            $context .= "- " . $apt['appointment_date'] . " with Dr. " . $apt['doctor_name'];
            $context .= " (" . $apt['specialization'] . ")";
            $context .= " - Status: " . $apt['status'];
            if ($apt['reason']) {
                $context .= " - Reason: " . $apt['reason'];
            }
            $context .= "\n";
        }
        $context .= "\n";
    }

    // Active prescriptions
    if (!empty($prescriptions)) {
        $context .= "=== YOUR ACTIVE PRESCRIPTIONS ===\n";
        foreach ($prescriptions as $rx) {
            $context .= "- " . $rx['medicine_name'];
            if ($rx['dosage'])
                $context .= " (" . $rx['dosage'] . ")";
            if ($rx['frequency'])
                $context .= " - " . $rx['frequency'];
            if ($rx['duration'])
                $context .= " for " . $rx['duration'];
            $context .= " - Prescribed by Dr. " . $rx['doctor_name'];
            $context .= " - Status: " . $rx['status'] . "\n";
        }
        $context .= "\n";
    }

    // Recent lab tests
    if (!empty($labTests)) {
        $context .= "=== YOUR RECENT LAB TESTS (Last 5) ===\n";
        foreach ($labTests as $test) {
            $context .= "- " . $test['test_name'] . " (" . $test['test_no'] . ")";
            $context .= " - Status: " . $test['status'];
            if ($test['result']) {
                $context .= " - Result: " . substr($test['result'], 0, 100);
            }
            $context .= " - Date: " . $test['created_at'] . "\n";
        }
        $context .= "\n";
    }

    // Recent medical records
    if (!empty($medicalRecords)) {
        $context .= "=== YOUR RECENT MEDICAL RECORDS (Last 5) ===\n";
        foreach ($medicalRecords as $rec) {
            $context .= "- Date: " . $rec['created_at'] . " - Doctor: Dr. " . $rec['doctor_name'] . "\n";
            if ($rec['chief_complaint'])
                $context .= "  Complaint: " . $rec['chief_complaint'] . "\n";
            if ($rec['diagnosis'])
                $context .= "  Diagnosis: " . $rec['diagnosis'] . "\n";
            if ($rec['treatment_plan'])
                $context .= "  Treatment: " . $rec['treatment_plan'] . "\n";
        }
        $context .= "\n";
    }

    return $context ?: "No medical data available yet.\n";
}

/**
 * Build the system prompt for the patient AI assistant
 */
function buildPatientPrompt($question, $context)
{
    return <<<PROMPT
You are a friendly AI Health Assistant for St. George Hospital's Patient Portal. You are speaking directly to a patient.

Your capabilities:
1. DOCTOR RECOMMENDATIONS: When the patient describes symptoms, recommend the most suitable doctor(s) from the available doctors list. Match symptoms to specializations. Always include the doctor's name, specialization, consultation fee, branch, and available schedule days/times.
2. MEDICAL HISTORY SUMMARY: Summarize the patient's medical history, appointments, prescriptions, and lab tests in plain, easy-to-understand language.
3. PRESCRIPTION INFO: Explain current medications, dosages, and frequencies.
4. LAB RESULTS: Help explain lab test results in simple terms.

Rules you MUST follow:
- ONLY use data provided below. Never invent doctors, appointments, or medical data.
- If no data is available for a question, say so honestly.
- Be warm, empathetic, and reassuring — you're helping a patient.
- For symptom-based doctor recommendations, explain WHY a particular specialist is suitable.
- Always mention that this is AI guidance, not a medical diagnosis.
- Keep responses concise but informative (under 300 words).
- When recommending a doctor, format it clearly with name, specialty, fee, and schedule.
- Do NOT attempt to diagnose conditions or prescribe medications.

PATIENT DATA:
{$context}

PATIENT QUESTION: {$question}

Provide a helpful, caring response based on the data above.
PROMPT;
}

/**
 * Call Gemini API (reuses same config as admin endpoint)
 */
function callGeminiAPI($prompt)
{
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        sendResponse(false, null, 'AI feature not configured. Please ask administration to set up the Gemini API key.', 500);
    }

    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => GEMINI_MAX_TOKENS,
            'temperature' => 0.7
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => GEMINI_TIMEOUT,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Patient AI Chat - cURL error: " . $curlError);
        sendResponse(false, null, 'Connection error. Please try again later.', 500);
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMessage = $responseData['error']['message'] ?? 'Unknown API error';
        error_log("Patient AI Chat - API error (HTTP $httpCode): " . $errorMessage);

        if ($httpCode === 429) {
            sendResponse(false, null, 'Too many requests. Please wait a moment and try again.', 429);
        }
        if ($httpCode === 403) {
            sendResponse(false, null, 'AI service authentication error. Please contact support.', 403);
        }

        sendResponse(false, null, 'AI service temporarily unavailable. Please try again.', 500);
    }

    $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        sendResponse(false, null, 'Empty response from AI. Please try rephrasing your question.', 500);
    }

    return $text;
}

// ==========================================
// MAIN REQUEST HANDLING
// ==========================================

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Method not allowed. Use POST.', 405);
}

// Validate session
validatePatientSession();

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendResponse(false, null, 'Invalid request body.', 400);
}

$question = trim($input['question'] ?? '');
if (empty($question)) {
    sendResponse(false, null, 'Please enter a question.', 400);
}
if (strlen($question) > 500) {
    sendResponse(false, null, 'Question is too long. Please keep it under 500 characters.', 400);
}

// Get patient profile ID
$userId = $_SESSION['user_id'];
$patientProfileId = getPatientProfileId($pdo, $userId);

// Fetch all context data
try {
    $doctors = fetchDoctorData($pdo);
    $profile = fetchPatientProfile($pdo, $userId);
    $appointments = $patientProfileId ? fetchRecentAppointments($pdo, $patientProfileId) : [];
    $prescriptions = $patientProfileId ? fetchActivePrescriptions($pdo, $patientProfileId) : [];
    $labTests = $patientProfileId ? fetchRecentLabTests($pdo, $patientProfileId) : [];
    $medicalRecords = $patientProfileId ? fetchMedicalRecords($pdo, $patientProfileId) : [];
} catch (PDOException $e) {
    error_log("Patient AI Chat - DB error: " . $e->getMessage());
    sendResponse(false, null, 'Error loading your health data. Please try again.', 500);
}

// Build context and prompt
$context = buildPatientContext($doctors, $profile, $appointments, $prescriptions, $labTests, $medicalRecords);
$prompt = buildPatientPrompt($question, $context);

// Call Gemini API
$aiResponse = callGeminiAPI($prompt);

// Return response
sendResponse(true, $aiResponse);
?>