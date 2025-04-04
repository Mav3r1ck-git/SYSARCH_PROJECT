<?php
require_once "database.php";

if (isset($_GET['searchQuery'])) {
    $searchQuery = trim($_GET['searchQuery']);

    $sql = "SELECT idNo, firstName, middleName, lastName, course, yearLevel, emailAddress 
            FROM users 
            WHERE idNo = ? OR lastName = ? OR firstName = ? OR middleName = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "idNo" => $row["idNo"],
            "fullName" => "{$row['firstName']} " . substr($row['middleName'], 0, 1) . ". {$row['lastName']}",
            "course" => $row["course"],
            "yearLevel" => $row["yearLevel"],
            "email" => $row["emailAddress"]
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
}
