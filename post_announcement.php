<?php
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST["announcement_content"];
    $datetime = date("Y-m-d H:i:s"); 

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcement (announcement_content, announcement_date) VALUES (?, ?)");
        $stmt->bind_param("ss", $content, $datetime);
        $stmt->execute();
        $stmt->close();
    }
}
?>
