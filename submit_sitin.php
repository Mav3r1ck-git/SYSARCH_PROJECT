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
    $pcNumber = $_POST["pc_number"];

    if ($sitinPurpose == "Others") {
        $sitinPurpose = $_POST["sitin_other_purpose"];
    }

    if (!empty($studentId) && !empty($sitinLab) && !empty($sitinPurpose) && !empty($pcNumber)) {
        // Directly insert into current_sitins table since this is admin-created
        $stmt = $conn->prepare("INSERT INTO current_sitins (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose, pc_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $studentId, $sitinLab, $sitinDate, $sitinTime, $sitinPurpose, $pcNumber);
        
        if ($stmt->execute()) {
            $_SESSION["message"] = "Sit-in created successfully!";
        } else {
            $_SESSION["error"] = "Error creating sit-in.";
        }
        
        $stmt->close();
    } else {
        $_SESSION["error"] = "All fields are required.";
    }
}

header("Location: admin_dashboard.php");
exit();
?> 