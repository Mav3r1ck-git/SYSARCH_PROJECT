<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

// Handle lab filter
$selectedLab = $_GET['lab'] ?? 'All';

$labFilterQuery = "";
$params = [];

if ($selectedLab !== 'All') {
    $labFilterQuery = "WHERE v.sitin_lab = ?";
    $params[] = $selectedLab;
}

$query = "
    SELECT v.*, u.lastName, u.firstName, u.middleName 
    FROM viewsessions v
    JOIN users u ON v.idNo = u.idNo
    $labFilterQuery
    ORDER BY v.session_id ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param("s", ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Logged Out Sessions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            <a href="admin_logout.php">Log-out</a>
        </div>
    </div>

    <div class="admin-sit-in-container">
        <h3>Logged Out Sessions</h3>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <label for="labFilter" class="form-label mb-0 me-2">View by Lab:</label>
                <select id="labFilter" class="form-select d-inline-block w-auto" onchange="filterByLab()">
                    <option value="All" <?= $selectedLab === 'All' ? 'selected' : '' ?>>All Labs</option>
                    <option value="Lab 1" <?= $selectedLab === 'Lab 1' ? 'selected' : '' ?>>Lab 1</option>
                    <option value="Lab 2" <?= $selectedLab === 'Lab 2' ? 'selected' : '' ?>>Lab 2</option>
                    <option value="Lab 3" <?= $selectedLab === 'Lab 3' ? 'selected' : '' ?>>Lab 3</option>
                    <option value="Lab 4" <?= $selectedLab === 'Lab 4' ? 'selected' : '' ?>>Lab 4</option>
                </select>
            </div>
            <div>
                <button class="btn btn-primary" onclick="printImage()">Save as Image</button>
                <a href="export_sessions.php?type=excel&lab=<?= urlencode($selectedLab) ?>" class="btn btn-success">Export to Excel</a>
                <a href="export_sessions.php?type=csv&lab=<?= urlencode($selectedLab) ?>" class="btn btn-warning">Export to CSV</a>
                <button class="btn btn-dark" onclick="printTableOnly()">Print</button>
            </div>
        </div>

        <div id="session-table">
            <table class="sit-in-table table table-bordered">
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
                            <td><?= $row["session_id"]; ?></td>
                            <td><?= $row["idNo"]; ?></td>
                            <td>
                                <?php
                                $lastName = htmlspecialchars($row["lastName"]);
                                $firstInitial = strtoupper(substr($row["firstName"], 0, 1)) . ".";
                                $middleInitial = (!empty($row["middleName"])) ? strtoupper(substr($row["middleName"], 0, 1)) . "." : "";
                                echo "$lastName, $firstInitial $middleInitial";
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row["sitin_lab"]); ?></td>
                            <td><?= date("F j, Y", strtotime($row["sitin_date"])); ?></td>
                            <td><?= date("h:i A", strtotime($row["sitin_time"])); ?></td>
                            <td><?= htmlspecialchars($row["sitin_purpose"]); ?></td>
                            <td><?= date("F j, Y, h:i A", strtotime($row["logged_out_at"])); ?></td>
                            <td>
                                <?php if (!empty($row["feedback"])): ?>
                                    <span class="text-success"><?= nl2br(htmlspecialchars($row["feedback"])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">No feedback provided.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function printImage() {
            const sessionTable = document.getElementById("session-table");
            html2canvas(sessionTable).then(canvas => {
                let link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "Logged_Out_Sessions_<?= $selectedLab ?>.png";
                link.click();
            });
        }

        function printTableOnly() {
            const tableContent = document.getElementById("session-table").innerHTML;
            const newWin = window.open("", "", "width=900,height=700");
            newWin.document.write(`
                <html>
                    <head>
                        <title>Print Table</title>
                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
                    </head>
                    <body>
                        <h3 class="text-center my-3">Logged Out Sessions</h3>
                        <table class="table table-bordered">${tableContent}</table>
                    </body>
                </html>
            `);
            newWin.document.close();
            newWin.focus();
            newWin.print();
            newWin.close();
        }

        function filterByLab() {
            const selectedLab = document.getElementById("labFilter").value;
            window.location.href = "admin_view_sessions.php?lab=" + encodeURIComponent(selectedLab);
        }
    </script>
</body>
</html>
