<?php
require_once "database.php";

if (!isset($_GET['type']) || !in_array($_GET['type'], ['excel', 'csv'])) {
    die("Invalid export type.");
}

$type = $_GET['type'];
$selectedLab = $_GET['lab'] ?? 'All';

$labFilter = "";
$params = [];

if ($selectedLab !== 'All') {
    $labFilter = "WHERE v.sitin_lab = ?";
    $params[] = $selectedLab;
}

$query = "
    SELECT v.session_id, v.idNo, u.lastName, u.firstName, u.middleName,
           v.sitin_lab, v.sitin_date, v.sitin_time, v.sitin_purpose,
           v.logged_out_at, v.feedback
    FROM viewsessions v
    JOIN users u ON v.idNo = u.idNo
    $labFilter
    ORDER BY v.session_id ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("s", ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = "Logged_Out_Sessions_" . str_replace(" ", "_", $selectedLab);

if ($type === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    echo "<table border='1'>";
    echo "<tr>
        <th>Session ID</th>
        <th>Student ID</th>
        <th>Student Name</th>
        <th>Lab</th>
        <th>Date</th>
        <th>Time</th>
        <th>Purpose</th>
        <th>Logged Out At</th>
        <th>Feedback</th>
    </tr>";

    while ($row = $result->fetch_assoc()) {
        $studentName = $row['lastName'] . ", " . strtoupper(substr($row['firstName'], 0, 1)) . ".";
        if (!empty($row['middleName'])) {
            $studentName .= strtoupper(substr($row['middleName'], 0, 1)) . ".";
        }

        echo "<tr>";
        echo "<td>{$row['session_id']}</td>";
        echo "<td>{$row['idNo']}</td>";
        echo "<td>{$studentName}</td>";
        echo "<td>{$row['sitin_lab']}</td>";
        echo "<td>{$row['sitin_date']}</td>";
        echo "<td>{$row['sitin_time']}</td>";
        echo "<td>{$row['sitin_purpose']}</td>";
        echo "<td>{$row['logged_out_at']}</td>";
        echo "<td>" . htmlspecialchars($row['feedback']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    // CSV
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"$filename.csv\"");

    $output = fopen("php://output", "w");
    fputcsv($output, [
        'Session ID', 'Student ID', 'Student Name', 'Lab', 'Date', 'Time', 'Purpose', 'Logged Out At', 'Feedback'
    ]);

    while ($row = $result->fetch_assoc()) {
        $studentName = $row['lastName'] . ", " . strtoupper(substr($row['firstName'], 0, 1)) . ".";
        if (!empty($row['middleName'])) {
            $studentName .= strtoupper(substr($row['middleName'], 0, 1)) . ".";
        }

        fputcsv($output, [
            $row['session_id'],
            $row['idNo'],
            $studentName,
            $row['sitin_lab'],
            $row['sitin_date'],
            $row['sitin_time'],
            $row['sitin_purpose'],
            $row['logged_out_at'],
            $row['feedback']
        ]);
    }

    fclose($output);
}
exit();
