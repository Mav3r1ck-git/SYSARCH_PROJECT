<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

$selectedLab = $_GET['lab'] ?? 'All';
$selectedPurpose = $_GET['purpose'] ?? 'All';

$filters = [];
$params = [];
$types = "";

// Add lab filter
if ($selectedLab !== 'All') {
    $filters[] = "v.sitin_lab = ?";
    $params[] = $selectedLab;
    $types .= "s";
}

// Add purpose filter
if ($selectedPurpose !== 'All') {
    $filters[] = "v.sitin_purpose = ?";
    $params[] = $selectedPurpose;
    $types .= "s";
}

$whereClause = "";
if (!empty($filters)) {
    $whereClause = "WHERE " . implode(" AND ", $filters);
}

$query = "
    SELECT v.*, u.lastName, u.firstName, u.middleName 
    FROM viewsessions v
    JOIN users u ON v.idNo = u.idNo
    $whereClause
    ORDER BY v.session_id ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <label class="form-label mb-0 me-2">View by Lab:</label>
                <select id="labFilter" class="form-select w-auto" onchange="applyFilters()">
                    <option value="All" <?= $selectedLab === 'All' ? 'selected' : '' ?>>All Labs</option>
                    <option value="Lab 524" <?= $selectedLab === 'Lab 524' ? 'selected' : '' ?>>Lab 524</option>
                    <option value="Lab 526" <?= $selectedLab === 'Lab 526' ? 'selected' : '' ?>>Lab 526</option>
                    <option value="Lab 528" <?= $selectedLab === 'Lab 528' ? 'selected' : '' ?>>Lab 528</option>
                    <option value="Lab 530" <?= $selectedLab === 'Lab 530' ? 'selected' : '' ?>>Lab 530</option>
                    <option value="Lab 542" <?= $selectedLab === 'Lab 542' ? 'selected' : '' ?>>Lab 542</option>
                    <option value="Lab 544" <?= $selectedLab === 'Lab 544' ? 'selected' : '' ?>>Lab 544</option>
                    <option value="Lab 517" <?= $selectedLab === 'Lab 517' ? 'selected' : '' ?>>Lab 517</option>
                </select>

                <label class="form-label mb-0 ms-3 me-2">View by Purpose:</label>
                <select id="purposeFilter" class="form-select w-auto" onchange="applyFilters()">
                    <option value="All" <?= $selectedPurpose === 'All' ? 'selected' : '' ?>>All Purposes</option>
                    <option value="C Programming" <?= $selectedPurpose === 'C Programming' ? 'selected' : '' ?>>C Programming</option>
                    <option value="Java Programming" <?= $selectedPurpose === 'Java Programming' ? 'selected' : '' ?>>Java Programming</option>
                    <option value="System Integration & Architecture" <?= $selectedPurpose === 'System Integration & Architecture' ? 'selected' : '' ?>>System Integration & Architecture</option>
                    <option value="Embeded System & IOT" <?= $selectedPurpose === 'Embeded System & IOT' ? 'selected' : '' ?>>Embeded System & IOT</option>
                    <option value="Digital Logic & Design" <?= $selectedPurpose === 'Digital Logic & Design' ? 'selected' : '' ?>>Digital Logic & Design</option>
                    <option value="Computer Application" <?= $selectedPurpose === 'Computer Application' ? 'selected' : '' ?>>Computer Application</option>
                    <option value="Database" <?= $selectedPurpose === 'Database' ? 'selected' : '' ?>>Database</option>
                    <option value="Project Management" <?= $selectedPurpose === 'Project Management' ? 'selected' : '' ?>>Project Management</option>
                    <option value="Python Programming" <?= $selectedPurpose === 'Python Programming' ? 'selected' : '' ?>>Python Programming</option>
                    <option value="Mobile Application" <?= $selectedPurpose === 'Mobile Application' ? 'selected' : '' ?>>Mobile Application</option>
                    <option value="Others" <?= $selectedPurpose === 'Others' ? 'selected' : '' ?>>Others</option>
                </select>
            </div>

            <div class="d-flex gap-2 mt-2 mt-md-0">
                <button class="btn btn-primary" onclick="printImage()">Save as Image</button>
                <a href="export_sessions.php?type=excel&lab=<?= urlencode($selectedLab) ?>&purpose=<?= urlencode($selectedPurpose) ?>" class="btn btn-success">Export to Excel</a>
                <a href="export_sessions.php?type=csv&lab=<?= urlencode($selectedLab) ?>&purpose=<?= urlencode($selectedPurpose) ?>" class="btn btn-warning">Export to CSV</a>
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
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            .center-text { text-align: center; margin-bottom: 10px; }
                        </style>
                    </head>
                    <body>
                        <div class="center-text">
                            <h3>University of Cebu-Main</h3>
                            <h4>College of Computer Studies</h4>
                            <h5>Computer Laboratory Sitin Monitoring</h5>
                            <h5>System Report</h5>
                        </div>
                        <table class="table table-bordered">${tableContent}</table>
                    </body>
                </html>
            `);
            newWin.document.close();
            newWin.focus();
            newWin.print();
            newWin.close();
        }

        function applyFilters() {
            const lab = document.getElementById("labFilter").value;
            const purpose = document.getElementById("purposeFilter").value;
            window.location.href = `admin_view_sessions.php?lab=${encodeURIComponent(lab)}&purpose=${encodeURIComponent(purpose)}`;
        }
    </script>
</body>
</html>
