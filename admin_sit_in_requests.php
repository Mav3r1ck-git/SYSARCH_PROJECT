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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout_user"])) {
    $idNo = $_POST["idNo"];
    $sitin_id = $_POST["sitin_id"];

    $fetchQuery = "SELECT sit_in_requests.*, users.userName FROM sit_in_requests 
                   JOIN users ON sit_in_requests.idNo = users.idNo 
                   WHERE sit_in_requests.sitin_id = ?";
    $stmt = $conn->prepare($fetchQuery);
    $stmt->bind_param("i", $sitin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sitinDetails = $result->fetch_assoc();
    $stmt->close();

    if ($sitinDetails) {
        $conn->begin_transaction();

        try {
            $insertQuery = "INSERT INTO viewsessions (idNo, userName, sitin_lab, sitin_date, sitin_time, sitin_purpose)
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param(
                "isssss",
                $sitinDetails["idNo"],
                $sitinDetails["userName"],
                $sitinDetails["sitin_lab"],
                $sitinDetails["sitin_date"],
                $sitinDetails["sitin_time"],
                $sitinDetails["sitin_purpose"]
            );
            $stmt->execute();
            $stmt->close();

            $updateQuery = "UPDATE users SET sessions = GREATEST(sessions - 1, 0) WHERE idNo = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $idNo);
            $stmt->execute();
            $stmt->close();

            $deleteQuery = "DELETE FROM sit_in_requests WHERE sitin_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $sitin_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $_SESSION["message"] = "Session deducted and sit-in record moved successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION["error"] = "Error processing request: " . $e->getMessage();
        }
    }

    header("Location: admin_sit_in_requests.php");
    exit();
}
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
        <h3>Current Sit-in Requests</h3>

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
                    <th>Date</th>
                    <th>Time</th>
                    <th>Purpose</th>
                    <th>Sessions Left</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row["sitin_id"]; ?></td>
                        <td><?php echo $row["idNo"]; ?></td>
                        <td><?php echo htmlspecialchars($row["userName"]); ?></td>
                        <td><?php echo $row["sitin_lab"]; ?></td>
                        <td><?php echo date("F j, Y", strtotime($row["sitin_date"])); ?></td>
                        <td><?php echo date("h:i A", strtotime($row["sitin_time"])); ?></td>
                        <td><?php echo htmlspecialchars($row["sitin_purpose"]); ?></td>
                        <td><?php echo $row["sessions"]; ?> / 30</td>
                        <td>
                            <?php if ($row["sessions"] > 0): ?>
                                <form method="post">
                                    <input type="hidden" name="idNo" value="<?php echo $row["idNo"]; ?>">
                                    <input type="hidden" name="sitin_id" value="<?php echo $row["sitin_id"]; ?>">
                                    <button type="submit" name="logout_user" class="btn btn-danger btn-sm">Log-out</button>
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
</body>
</html>
