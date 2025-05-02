<?php
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lab = $_POST['lab'] ?? '';
    $pc_number = $_POST['pc_number'] ?? '';
    $available = isset($_POST['available']) ? intval($_POST['available']) : 1;

    if ($lab && $pc_number) {
        // Insert or update the availability
        $stmt = $conn->prepare(
            "INSERT INTO pc_availability (lab, pc_number, available)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE available = VALUES(available)"
        );
        $stmt->bind_param("ssi", $lab, $pc_number, $available);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>