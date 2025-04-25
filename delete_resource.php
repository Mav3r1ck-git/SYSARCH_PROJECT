<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    
    // Get file path before deleting
    $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $filePath = $row["file_path"];
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the actual file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $_SESSION["message"] = "Resource deleted successfully.";
    } else {
        $_SESSION["error"] = "Error deleting resource.";
    }
    $stmt->close();
}

header("Location: admin_upload_resources.php");
exit();
?> 