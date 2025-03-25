<?php
require_once "database.php";

$query = "SELECT * FROM announcement ORDER BY announcement_date DESC";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "<li><strong>" . date("F j, Y", strtotime($row["announcement_date"])) . "</strong><br>";
    echo nl2br(htmlspecialchars($row["announcement_content"])) . "</li><hr>";
}
?>
