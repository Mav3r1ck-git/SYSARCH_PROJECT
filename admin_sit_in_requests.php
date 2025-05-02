<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT sit_in_requests.sitin_id, sit_in_requests.sitin_lab, sit_in_requests.sitin_date, sit_in_requests.sitin_time, sit_in_requests.sitin_purpose, 
                 users.idNo, users.userName, users.sessions 
          FROM sit_in_requests 
          JOIN users ON sit_in_requests.idNo = users.idNo 
          ORDER BY sit_in_requests.sitin_date DESC, sit_in_requests.sitin_time DESC";
$result = $conn->query($query);

// Handle logout action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout_user"])) {
    $idNo = $_POST["idNo"];
    $sitin_id = $_POST["sitin_id"];

    $conn->begin_transaction();

    try {
        // Get sit-in details
        $stmt = $conn->prepare("SELECT * FROM current_sitins WHERE sitin_id = ?");
        $stmt->bind_param("i", $sitin_id);
        $stmt->execute();
        $sitinDetails = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sitinDetails) {
            // Insert into viewsessions (logged out sessions)
            $stmt = $conn->prepare("INSERT INTO viewsessions (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose, pc_number, logged_out_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $sitinDetails["idNo"], $sitinDetails["sitin_lab"], 
                            $sitinDetails["sitin_date"], $sitinDetails["sitin_time"], 
                            $sitinDetails["sitin_purpose"], $sitinDetails["pc_number"]);
            $stmt->execute();
            $stmt->close();

            // Deduct 1 session from user's total sessions
            $stmt = $conn->prepare("UPDATE users SET sessions = GREATEST(sessions - 1, 0) WHERE idNo = ?");
            $stmt->bind_param("i", $idNo);
            $stmt->execute();
            $stmt->close();

            // Delete from current_sitins
            $stmt = $conn->prepare("DELETE FROM current_sitins WHERE sitin_id = ?");
            $stmt->bind_param("i", $sitin_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION["message"] = "User logged out successfully. 1 session deducted.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION["error"] = "Error processing request: " . $e->getMessage();
    }

    header("Location: admin_sit_in_requests.php");
    exit();
}

// Handle reward action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reward_user"])) {
    $idNo = $_POST["idNo"];
    $sitin_id = $_POST["sitin_id"];

    $conn->begin_transaction();

    try {
        // Get sit-in details
        $stmt = $conn->prepare("SELECT * FROM current_sitins WHERE sitin_id = ?");
        $stmt->bind_param("i", $sitin_id);
        $stmt->execute();
        $sitinDetails = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sitinDetails) {
            // Get current points and sessions
            $stmt = $conn->prepare("SELECT points, sessions FROM users WHERE idNo = ?");
            $stmt->bind_param("i", $idNo);
            $stmt->execute();
            $stmt->bind_result($currentPoints, $currentSessions);
            $stmt->fetch();
            $stmt->close();

            // Calculate new points and check if we should add a session
            $newPoints = $currentPoints + 1;
            $addSession = ($newPoints % 3 == 0) ? 1 : 0;
            $newSessions = $currentSessions + $addSession;

            // Update points and sessions
            $stmt = $conn->prepare("UPDATE users SET points = ?, sessions = ? WHERE idNo = ?");
            $stmt->bind_param("iii", $newPoints, $newSessions, $idNo);
            $stmt->execute();
            $stmt->close();

            // Insert into viewsessions (logged out sessions)
            $stmt = $conn->prepare("INSERT INTO viewsessions (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose, pc_number, logged_out_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssss", $sitinDetails["idNo"], $sitinDetails["sitin_lab"], 
                            $sitinDetails["sitin_date"], $sitinDetails["sitin_time"], 
                            $sitinDetails["sitin_purpose"], $sitinDetails["pc_number"]);
            $stmt->execute();
            $stmt->close();

            // Delete from current_sitins
            $stmt = $conn->prepare("DELETE FROM current_sitins WHERE sitin_id = ?");
            $stmt->bind_param("i", $sitin_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            if ($addSession) {
                $_SESSION["message"] = "User rewarded with 1 point, gained 1 bonus session, and logged out successfully.";
            } else {
                $_SESSION["message"] = "User rewarded with 1 point and logged out successfully.";
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION["error"] = "Error processing request: " . $e->getMessage();
    }

    header("Location: admin_sit_in_requests.php");
    exit();
}

// Handle approval of sit-in requests
if (isset($_POST['approve_request'])) {
    $sitin_id = $_POST['sitin_id'];
    
    // Get the request details
    $stmt = $conn->prepare("SELECT * FROM sit_in_requests WHERE sitin_id = ?");
    $stmt->bind_param("i", $sitin_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($request) {
        // Move to current sit-ins
        $stmt = $conn->prepare("INSERT INTO current_sitins (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose, pc_number) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $request['idNo'], $request['sitin_lab'], $request['sitin_date'], 
                         $request['sitin_time'], $request['sitin_purpose'], $request['pc_number']);
        $stmt->execute();
        $stmt->close();

        // Delete from requests
        $stmt = $conn->prepare("DELETE FROM sit_in_requests WHERE sitin_id = ?");
        $stmt->bind_param("i", $sitin_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION["message"] = "Sit-in request approved successfully.";
    }
}

// Handle rejection of sit-in requests
if (isset($_POST['reject_request'])) {
    $sitin_id = $_POST['sitin_id'];
    
    $stmt = $conn->prepare("DELETE FROM sit_in_requests WHERE sitin_id = ?");
    $stmt->bind_param("i", $sitin_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION["message"] = "Sit-in request rejected successfully.";
}

// Fetch pending sit-in requests
$pendingRequestsQuery = "SELECT sit_in_requests.*, users.userName 
                        FROM sit_in_requests 
                        JOIN users ON sit_in_requests.idNo = users.idNo 
                        ORDER BY sit_in_requests.sitin_date ASC, sit_in_requests.sitin_time ASC";
$pendingRequests = $conn->query($pendingRequestsQuery);

// Fetch current sit-ins
$currentSitinsQuery = "SELECT current_sitins.*, users.userName, users.sessions 
                      FROM current_sitins 
                      JOIN users ON current_sitins.idNo = users.idNo 
                      ORDER BY current_sitins.sitin_date DESC, current_sitins.sitin_time DESC";
$currentSitins = $conn->query($currentSitinsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sit-in Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="sit-in-request-body">
    <div class="header-container">
        <div class="logo-title">
            <img src="ccs.png" alt="Logo" class="logo">
            <h2>Sit-in Requests</h2>
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
        <button class="btn btn-info mb-3" id="viewAvailablePcBtn" type="button">View Available PC</button>
        <div id="availablePcSection" style="display:none;" class="mb-4">
            <form id="availablePcForm" method="post" action="">
                <div class="mb-3">
                    <label for="labSelect" class="form-label">Select Lab:</label>
                    <select id="labSelect" name="lab" class="form-control" required>
                        <option value="" disabled selected>Select Lab</option>
                        <option value="Lab 524">Lab 524</option>
                        <option value="Lab 526">Lab 526</option>
                        <option value="Lab 528">Lab 528</option>
                        <option value="Lab 530">Lab 530</option>
                        <option value="Lab 542">Lab 542</option>
                        <option value="Lab 544">Lab 544</option>
                        <option value="Lab 517">Lab 517</option>
                    </select>
                </div>
                <div id="pcCheckboxes" class="row"></div>
            </form>
        </div>
        <h3>Pending Sit-in Requests</h3>

        <?php if (isset($_SESSION["message"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["message"]; unset($_SESSION["message"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <table class="sit-in-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Lab</th>
                    <th>PC Number</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $pendingRequests->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row["sitin_id"]; ?></td>
                        <td><?php echo $row["idNo"]; ?></td>
                        <td><?php echo htmlspecialchars($row["userName"]); ?></td>
                        <td><?php echo $row["sitin_lab"]; ?></td>
                        <td><?php echo $row["pc_number"]; ?></td>
                        <td><?php echo date("F j, Y", strtotime($row["sitin_date"])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row["sitin_time"])); ?></td>
                        <td><?php echo htmlspecialchars($row["sitin_purpose"]); ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="sitin_id" value="<?php echo $row["sitin_id"]; ?>">
                                <button type="submit" name="approve_request" class="btn btn-success btn-sm">Approve</button>
                                <button type="submit" name="reject_request" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="mt-4">Current Sit-ins</h3>
        <table class="sit-in-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Lab</th>
                    <th>PC Number</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Sessions Left</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $currentSitins->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row["sitin_id"]; ?></td>
                        <td><?php echo $row["idNo"]; ?></td>
                        <td><?php echo htmlspecialchars($row["userName"]); ?></td>
                        <td><?php echo $row["sitin_lab"]; ?></td>
                        <td><?php echo $row["pc_number"]; ?></td>
                        <td><?php echo date("F j, Y", strtotime($row["sitin_date"])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row["sitin_time"])); ?></td>
                        <td><?php echo htmlspecialchars($row["sitin_purpose"]); ?></td>
                        <td><?php echo $row["sessions"]; ?> / 30</td>
                        <td>
                            <?php if ($row["sessions"] > 0): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="idNo" value="<?php echo $row["idNo"]; ?>">
                                    <input type="hidden" name="sitin_id" value="<?php echo $row["sitin_id"]; ?>">
                                    <button type="submit" name="logout_user" class="btn btn-danger btn-sm">Log-out</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="idNo" value="<?php echo $row["idNo"]; ?>">
                                    <input type="hidden" name="sitin_id" value="<?php echo $row["sitin_id"]; ?>">
                                    <button type="submit" name="reward_user" class="btn btn-success btn-sm">Reward</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>No Sessions Left</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    document.getElementById('viewAvailablePcBtn').addEventListener('click', function() {
        const section = document.getElementById('availablePcSection');
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('labSelect').addEventListener('change', function() {
        const lab = this.value;
        const pcDiv = document.getElementById('pcCheckboxes');
        pcDiv.innerHTML = '';
        for (let i = 1; i <= 50; i++) {
            const pcNum = 'PC' + i;
            const col = document.createElement('div');
            col.className = 'col-2 mb-2';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = lab + '_' + pcNum;
            checkbox.name = 'pcs[]';
            checkbox.value = pcNum;
            checkbox.className = 'form-check-input me-1';
            checkbox.addEventListener('change', function() {
                // AJAX to update DB
                fetch('update_pc_availability.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `lab=${encodeURIComponent(lab)}&pc_number=${encodeURIComponent(pcNum)}&available=${checkbox.checked ? 1 : 0}`
                });
            });
            const label = document.createElement('label');
            label.htmlFor = checkbox.id;
            label.textContent = pcNum;
            col.appendChild(checkbox);
            col.appendChild(label);
            pcDiv.appendChild(col);
        }
    });
    </script>
</body>
</html>
