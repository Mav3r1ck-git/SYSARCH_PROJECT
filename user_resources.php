<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Fetch all resources
$resourcesQuery = "SELECT * FROM resources ORDER BY upload_date DESC";
$resourcesResult = $conn->query($resourcesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Resources</h2>
        <div class="nav-bar">
        <a href="dashboard.php">Home</a>
            <a href="editProfile.php">Edit Profile</a>
            <a href="sitInHistory.php">Sit-in History</a>
            <a href="reservation.php">Reservation</a>
            <a href="login.php">Log-out</a>
        </div>
    </div>

    <div class="admin-sit-in-container">
        <h3>Available Resources</h3>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Description</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resourcesResult->num_rows > 0): ?>
                            <?php while ($row = $resourcesResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row["file_name"]) ?></td>
                                    <td><?= htmlspecialchars($row["file_type"]) ?></td>
                                    <td><?= formatFileSize($row["file_size"]) ?></td>
                                    <td><?= htmlspecialchars($row["description"]) ?></td>
                                    <td><?= date("F j, Y, h:i A", strtotime($row["upload_date"])) ?></td>
                                    <td>
                                        <a href="<?= $row["file_path"] ?>" class="btn btn-sm btn-primary" download>
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No resources available yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?> 