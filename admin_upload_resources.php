<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit();
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["upload_resource"])) {
    $fileName = $_FILES["resource_file"]["name"];
    $fileType = $_FILES["resource_file"]["type"];
    $fileSize = $_FILES["resource_file"]["size"];
    $fileTmp = $_FILES["resource_file"]["tmp_name"];
    $description = $_POST["description"];
    $uploadDate = date("Y-m-d H:i:s");

    // Create uploads directory if it doesn't exist
    if (!file_exists("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($fileName);
    $uploadOk = 1;

    // Check if file already exists
    if (file_exists($targetFile)) {
        $_SESSION["error"] = "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (10MB max)
    if ($fileSize > 10000000) {
        $_SESSION["error"] = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    $allowedTypes = array("pdf", "doc", "docx", "txt", "jpg", "jpeg", "png", "ppt", "pptx", "xls", "xlsx", "zip", "rar");
    $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        $_SESSION["error"] = "Sorry, only PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, PPT, PPTX, XLS, XLSX, ZIP, RAR files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($fileTmp, $targetFile)) {
            $stmt = $conn->prepare("INSERT INTO resources (file_name, file_path, file_type, file_size, description, upload_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fileName, $targetFile, $fileType, $fileSize, $description, $uploadDate);
            
            if ($stmt->execute()) {
                $_SESSION["message"] = "The file " . htmlspecialchars(basename($fileName)) . " has been uploaded.";
            } else {
                $_SESSION["error"] = "Sorry, there was an error uploading your file.";
            }
            $stmt->close();
        } else {
            $_SESSION["error"] = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch all resources
$resourcesQuery = "SELECT * FROM resources ORDER BY upload_date DESC";
$resourcesResult = $conn->query($resourcesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Resources</h2>
        <div class="nav-bar">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_announcements.php">Announcements</a>
            <a href="admin_sit_in_requests.php">Sit-in Requests</a>
            <a href="admin_view_sessions.php">Logged Out Sessions</a>
            <a href="admin_upload_resources.php" class="active">Resources</a>
            <a href="admin_logout.php">Log-out</a>
        </div>
    </div>

    <div class="admin-sit-in-container">
        <h3>Upload Resources</h3>

        <?php if (isset($_SESSION["message"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["message"]; unset($_SESSION["message"]); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="resource_file" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="resource_file" name="resource_file" required>
                        <div class="form-text">Maximum file size: 10MB. Allowed types: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, PPT, PPTX, XLS, XLSX, ZIP, RAR</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="upload_resource" class="btn btn-primary">Upload Resource</button>
                </form>
            </div>
        </div>

        <h3>Uploaded Resources</h3>
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
                                        <a href="delete_resource.php?id=<?= $row["id"] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this resource?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No resources uploaded yet.</td>
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
