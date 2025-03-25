<?php
session_start();
require_once "database.php"; 

if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$admin = $_SESSION["admin"];
$adminName = "{$admin['adminFirstName']} " . substr($admin['adminMiddleName'], 0, 1) . ". {$admin['adminLastName']}";

$studentCountQuery = "SELECT COUNT(*) as total_students FROM users";
$result = mysqli_query($conn, $studentCountQuery);
$studentData = mysqli_fetch_assoc($result);
$totalStudents = $studentData['total_students'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body class="dashboard-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Admin Dashboard</h2>
        <div class="nav-bar">
        <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_announcements.php">Announcements</a>
            <a href="admin_sit_in_requests.php">Sit-in Requests</a>
            <a href="admin_view_sessions.php" class="active">Logged Out Sessions</a>
            <a href="logout.php">Log-out</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="stats-container card">
            <h3><center>Student Statistics</center></h3>
            <canvas id="studentChart"></canvas>
        </div>

        <div class="search-container card">
            <h3><center>Search Students</center></h3>
            <input type="text" class="form-control mb-3" id="searchQuery" placeholder="Search by ID, Last Name, First Name, Middle Name">
            <button type="button" class="btn btn-primary w-100" id="searchBtn">Search</button>
        </div>
    </div>

    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Student Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <span id="studentName"></span></p>
                    <p><strong>Course:</strong> <span id="studentCourse"></span></p>
                    <p><strong>Year Level:</strong> <span id="studentYear"></span></p>
                    <p><strong>Email:</strong> <span id="studentEmail"></span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('studentChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Registered Students'],
                datasets: [{
                    data: [<?php echo $totalStudents; ?>],
                    backgroundColor: ['#36A2EB'],
                    hoverBackgroundColor: ['#1E90FF']
                }]
            }
        });

        $("#searchBtn").click(function () {
            let query = $("#searchQuery").val().trim();

            if (query === "") {
                alert("Please enter a search term.");
                return;
            }

            $.ajax({
                url: "search_student.php",
                type: "GET",
                data: { searchQuery: query },
                success: function (response) {
                    let data = JSON.parse(response);
                    if (data.success) {
                        $("#studentName").text(data.fullName);
                        $("#studentCourse").text(data.course);
                        $("#studentYear").text(data.yearLevel);
                        $("#studentEmail").text(data.email);
                        $("#studentModal").modal("show");
                    } else {
                        alert("Student not found!");
                    }
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
