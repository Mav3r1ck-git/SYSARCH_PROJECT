<?php
require_once "database.php";

if (isset($_GET['searchQuery'])) {
    $searchQuery = trim($_GET['searchQuery']);

    $sql = "SELECT idNo, firstName, middleName, lastName, course, yearLevel, emailAddress 
            FROM users 
            WHERE idNo = ? OR lastName = ? OR firstName = ? OR middleName = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response = [
            "success" => true,
            "fullName" => "{$row['firstName']} " . substr($row['middleName'], 0, 1) . ". {$row['lastName']}",
            "course" => $row["course"],
            "yearLevel" => $row["yearLevel"],
            "email" => $row["emailAddress"]
        ];
    } else {
        $response = ["success" => false];
    }

    echo json_encode($response);
}
?>
