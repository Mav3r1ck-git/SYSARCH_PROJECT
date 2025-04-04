<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM announcement ORDER BY announcement_date DESC";
$result = $conn->query($query);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_announcement"])) {
    $content = $_POST["announcement_content"];
    $date = date("Y-m-d H:i:s"); 

    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcement (announcement_content, announcement_date) VALUES (?, ?)");
        $stmt->bind_param("ss", $content, $date);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_announcements.php"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Announcements</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-announcement-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 80%;
            margin: auto;
            margin-top: 20px;
        }
        .announcement-form, .announcement-list {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="admin-announcement-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Announcements</h2>
        <div class="nav-bar">
        <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_announcements.php">Announcements</a>
            <a href="admin_sit_in_requests.php">Sit-in Requests</a>
            <a href="admin_view_sessions.php" class="active">Logged Out Sessions</a>
            <a href="admin_logout.php">Log-out</a>
        </div>
    </div>

    <div class="admin-announcement-container">
        <div class="announcement-form card">
            <h3>Create Announcement</h3>
            <form method="POST">
                <textarea name="announcement_content" class="form-control" rows="4" placeholder="Write your announcement here..." required></textarea>
                <button type="submit" name="submit_announcement" class="btn btn-primary mt-2">Post Announcement</button>
            </form>
        </div>

        <div class="announcement-list card">
            <h3>All Announcements</h3>
            <ul>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li>
                        <strong><?php echo date("F j, Y h:i A", strtotime($row["announcement_date"])); ?></strong><br>
                        <?php echo nl2br(htmlspecialchars($row["announcement_content"])); ?>
                    </li>
                    <hr>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</body>
</html>
