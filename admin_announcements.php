<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcement WHERE announcement_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_announcements.php");
    exit();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_announcement"])) {
    $id = intval($_POST["announcement_id"]);
    $content = $_POST["announcement_content"];
    $stmt = $conn->prepare("UPDATE announcement SET announcement_content = ? WHERE announcement_id = ?");
    $stmt->bind_param("si", $content, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_announcements.php");
    exit();
}

// Handle new announcement
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

$query = "SELECT * FROM announcement ORDER BY announcement_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Announcements</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-announcement-container {
            display: flex;
            flex-direction: column;
            align-items: center;
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
            <a href="admin_announcements.php" class="active">Announcements</a>
            <a href="admin_sit_in_requests.php">Sit-in Requests</a>
            <a href="admin_view_sessions.php">Logged Out Sessions</a>
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
            <ul class="list-group">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <div>
                            <strong><?php echo date("F j, Y h:i A", strtotime($row["announcement_date"])); ?></strong><br>
                            <div class="mt-2"><?php echo nl2br(htmlspecialchars($row["announcement_content"])); ?></div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['announcement_id'] ?>">Edit</button>
                                <a href="?delete=<?= $row['announcement_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
                            </div>
                        </div>
                    </li>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['announcement_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Announcement</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="announcement_id" value="<?= $row['announcement_id'] ?>">
                                        <textarea name="announcement_content" class="form-control" rows="4" required><?= htmlspecialchars($row["announcement_content"]) ?></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="update_announcement" class="btn btn-success">Save Changes</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
