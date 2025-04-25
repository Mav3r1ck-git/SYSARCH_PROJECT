<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];
$idNo = $user['idNo']; // Get user idNo from session

// Fetch current sit-in requests only for the logged-in user
$sitInQuery = "SELECT * FROM sit_in_requests WHERE idNo = ? AND sitin_date >= CURDATE()";
$stmt = $conn->prepare($sitInQuery);
$stmt->bind_param("s", $idNo);
$stmt->execute();
$currentRequests = $stmt->get_result();
$stmt->close();

// Fetch past sit-ins (only logged-out sessions) for the logged-in user
$historyQuery = "SELECT * FROM viewsessions WHERE idNo = ? AND logged_out_at IS NOT NULL ORDER BY sitin_date DESC, sitin_time DESC";
$stmt = $conn->prepare($historyQuery);
$stmt->bind_param("s", $idNo);
$stmt->execute();
$pastSessions = $stmt->get_result();
$stmt->close();

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $session_id = $_POST['session_id'];
    $feedback = trim($_POST['feedback']);

    // Check if feedback already exists
    $checkQuery = "SELECT feedback FROM viewsessions WHERE session_id = ? AND feedback IS NOT NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) { // No feedback exists yet
        $stmt->close();

        // Insert feedback
        $updateQuery = "UPDATE viewsessions SET feedback = ? WHERE session_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $feedback, $session_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Feedback submitted successfully!";
    } else {
        $_SESSION['message'] = "Feedback already submitted for this session.";
    }

    header("Location: sitInHistory.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body class="sit-in-history-body">
    <div class="header-container">
        <div class="logo-title">
            <h2><img src="ccs.png" alt="Logo" class="logo"> Sit-in History</h2>
        </div>
        <div class="nav-bar">
            <a href="dashboard.php">Home</a>
            <a href="editProfile.php">Edit Profile</a>
            <a href="sitInHistory.php">Sit-in History</a>
            <a href="reservation.php">Reservation</a>
            <a href="user_resources.php">Resources</a>
            <a href="logout.php">Log-out</a>
        </div>
    </div>

    <div class="container mt-4" style="padding-bottom: 50px;">
        <h1 class="text-center">Sit-in History</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']); 
                ?>
            </div>
        <?php endif; ?>

        <!-- Current Sit-in Requests -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Current Sit-in Requests</h4>
            </div>
            <div class="card-body">
                <?php if ($currentRequests->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $currentRequests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['sitin_lab']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_purpose']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No current sit-in requests.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Sit-in History -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4>Past Sit-in Sessions</h4>
            </div>
            <div class="card-body">
                <?php if ($pastSessions->num_rows > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Lab</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Logged Out</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pastSessions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['sitin_lab']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sitin_purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($row['logged_out_at']); ?></td>
                                    <td>
                                        <?php if (!empty($row['feedback'])): ?>
                                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($row['feedback'])); ?></p>
                                        <?php else: ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="session_id" value="<?php echo $row['session_id']; ?>">
                                                <textarea name="feedback" class="form-control" rows="2" placeholder="Enter feedback" required></textarea>
                                                <button type="submit" name="submit_feedback" class="btn btn-sm btn-primary mt-2">Submit</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No past sit-ins found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
