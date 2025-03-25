<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

$query = "
    SELECT v.*, u.lastName, u.firstName, u.middleName 
    FROM viewsessions v
    JOIN users u ON v.idNo = u.idNo
    ORDER BY v.session_id ASC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Logged Out Sessions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function printImage() {
            html2canvas(document.getElementById("session-table")).then(canvas => {
                let link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Logged_Out_Sessions.png";
                link.click();
            });
        }
    </script>
</head>
<body class="sit-in-history-body">
    <div class="header-container">
        <div class="logo-title">
            <img src="ccs.png" alt="Logo" class="logo">
            <h2>Logged Out Sessions</h2>
        </div>
        <div class="nav-bar">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_announcements.php">Announcements</a>
            <a href="admin_sit_in_requests.php">Sit-in Requests</a>
            <a href="admin_view_sessions.php" class="active">Logged Out Sessions</a>
            <a href="logout.php">Log-out</a>
        </div>
    </div>

    <div class="admin-sit-in-container">
        <h3>All Logged Out Sessions</h3>

        <div class="mb-3">
            <button class="btn btn-primary" onclick="printImage()">Save as Image</button>
            <a href="export_sessions.php?type=excel" class="btn btn-success">Export to Excel</a>
            <a href="export_sessions.php?type=csv" class="btn btn-warning">Export to CSV</a>
        </div>

        <table class="sit-in-table table table-bordered" id="session-table">
            <thead class="table-dark">
                <tr>
                    <th>Session ID</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Lab</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Logged Out At</th>
                    <th>Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row["session_id"]; ?></td>
                        <td><?php echo $row["idNo"]; ?></td>
                        <td>
                            <?php
                            $lastName = htmlspecialchars($row["lastName"]);
                            $firstInitial = strtoupper(substr($row["firstName"], 0, 1)) . ".";
                            $middleInitial = (!empty($row["middleName"])) ? strtoupper(substr($row["middleName"], 0, 1)) . "." : "";

                            echo "$lastName, $firstInitial $middleInitial";
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row["sitin_lab"]); ?></td>
                        <td><?php echo date("F j, Y", strtotime($row["sitin_date"])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row["sitin_time"])); ?></td>
                        <td><?php echo htmlspecialchars($row["sitin_purpose"]); ?></td>
                        <td><?php echo date("F j, Y, h:i A", strtotime($row["logged_out_at"])); ?></td>
                        <td>
                            <?php if (!empty($row["feedback"])): ?>
                                <span class="text-success"><?php echo nl2br(htmlspecialchars($row["feedback"])); ?></span>
                            <?php else: ?>
                                <span class="text-muted">No feedback provided.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
