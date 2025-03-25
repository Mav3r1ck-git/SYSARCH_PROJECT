<?php
require_once "database.php";

if (isset($_GET["type"])) {
    $type = $_GET["type"];

    $query = "SELECT * FROM viewsessions ORDER BY logged_out_at DESC";
    $result = $conn->query($query);
    
    if ($type == "excel") {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Logged_Out_Sessions.xls");

        echo "Session ID\tStudent ID\tStudent Name\tLab\tDate\tTime\tPurpose\tLogged Out At\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['session_id']}\t{$row['idNo']}\t{$row['userName']}\t{$row['sitin_lab']}\t";
            echo date("F j, Y", strtotime($row["sitin_date"])) . "\t";
            echo date("h:i A", strtotime($row["sitin_time"])) . "\t";
            echo "{$row['sitin_purpose']}\t";
            echo date("F j, Y, h:i A", strtotime($row["logged_out_at"])) . "\n";
        }
        exit();
    } elseif ($type == "csv") {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=Logged_Out_Sessions.csv");

        $output = fopen("php://output", "w");
        fputcsv($output, ["Session ID", "Student ID", "Student Name", "Lab", "Date", "Time", "Purpose", "Logged Out At"]);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['session_id'], $row['idNo'], $row['userName'], $row['sitin_lab'],
                date("F j, Y", strtotime($row["sitin_date"])),
                date("h:i A", strtotime($row["sitin_time"])),
                $row['sitin_purpose'],
                date("F j, Y, h:i A", strtotime($row["logged_out_at"]))
            ]);
        }
        fclose($output);
        exit();
    }
}
?>
