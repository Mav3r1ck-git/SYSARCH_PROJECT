<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_request"])) {
    $studentId = $_POST["student_id"];
    $sitinLab = $_POST["sitin_lab"];
    $sitinDate = date('Y-m-d'); // Always use current date
    $sitinTime = date('H:i'); // Always use current time
    $sitinPurpose = $_POST["sitin_purpose"];

    if ($sitinPurpose == "Others") {
        $sitinPurpose = $_POST["sitin_other_purpose"];
    }

    if (!empty($studentId) && !empty($sitinLab) && !empty($sitinPurpose)) {
        $stmt = $conn->prepare("INSERT INTO sit_in_requests (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $studentId, $sitinLab, $sitinDate, $sitinTime, $sitinPurpose);
        
        if ($stmt->execute()) {
            $_SESSION["message"] = "Sit-in request submitted successfully!";
        } else {
            $_SESSION["error"] = "Error submitting sit-in request.";
        }
        
        $stmt->close();
    } else {
        $_SESSION["error"] = "All fields are required.";
    }
}

header("Location: admin_dashboard.php");
exit();
?> 