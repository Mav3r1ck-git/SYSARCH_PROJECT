<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];
$studentId = $user['idNo']; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_request"])) {
    $sitinLab = $_POST["sitin_lab"];
    $sitinDate = $_POST["sitin_date"];
    $sitinTime = $_POST["sitin_time"];
    $sitinPurpose = $_POST["sitin_purpose"];

    if ($sitinPurpose == "Others") {
        $sitinPurpose = $_POST["sitin_other_purpose"];
    }

    if (!empty($sitinLab) && !empty($sitinDate) && !empty($sitinTime) && !empty($sitinPurpose)) {
        $stmt = $conn->prepare("INSERT INTO sit_in_requests (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $studentId, $sitinLab, $sitinDate, $sitinTime, $sitinPurpose);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>
                alert('Sit-in request submitted successfully!');
                window.location.href = 'reservation.php';
              </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="reservation-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Sit-in Reservation</h2>
        <div class="nav-bar">
            <a href="dashboard.php">Home</a>
            <a href="editProfile.php">Edit Profile</a>
            <a href="sitInHistory.php">Sit-in History</a>
            <a href="reservation.php" class="active">Reservation</a>
            <a href="user_resources.php">Resources</a>
            <a href="logout.php">Log-out</a>
        </div>
    </div>

    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="reservation-container card p-4 shadow" style="width: 40%;">
            <h3 class="text-center">Reserve a Sit-in</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="sitin_lab" class="form-label">Select Lab:</label>
                    <select name="sitin_lab" class="form-control" required>
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
                <div class="mb-3">
                    <label for="sitin_date" class="form-label">Date:</label>
                    <input type="date" name="sitin_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="sitin_time" class="form-label">Time:</label>
                    <input type="time" name="sitin_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="sitin_purpose" class="form-label">Purpose:</label>
                    <select name="sitin_purpose" class="form-control" required onchange="toggleOtherPurpose(this)">
                        <option value="" disabled selected>Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="Java Programming">Java Programming</option>
                        <option value="System Integration & Architecture">System Integration & Architecture</option>
                        <option value="Embeded System & IOT">Embeded System & IOT</option>
                        <option value="Digital Logic & Design">Digital Logic & Design</option>
                        <option value="Computer Application">Computer Application</option>
                        <option value="Database">Database</option>
                        <option value="Project Management">Project Management</option>
                        <option value="Python Programming">Python Programming</option>
                        <option value="Mobile Application">Mobile Application</option>
                        <option value="Others">Others</option>
                    </select>
                    <input type="text" name="sitin_other_purpose" class="form-control mt-2 d-none" placeholder="Specify if 'Others'">
                </div>
                <button type="submit" name="submit_request" class="btn btn-primary w-100">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        function toggleOtherPurpose(select) {
            const otherInput = document.querySelector('input[name="sitin_other_purpose"]');
            if (select.value === "Others") {
                otherInput.classList.remove("d-none");
                otherInput.setAttribute("required", true);
            } else {
                otherInput.classList.add("d-none");
                otherInput.removeAttribute("required");
            }
        }
    </script>
</body>
</html>
