<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

$admin = $_SESSION["admin"];

// Delete student
if (isset($_GET['delete_id'])) {
    $idNo = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE idNo = ?");
    $stmt->bind_param("i", $idNo);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Student deleted successfully.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

// Reset sessions
if (isset($_GET['reset_id'])) {
    $idNo = $_GET['reset_id'];
    $stmt = $conn->prepare("UPDATE users SET sessions = 30 WHERE idNo = ?");
    $stmt->bind_param("i", $idNo);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Sessions reset to 30.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

// Sit-in request
if (isset($_POST['sit_in_submit'])) {
    $idNo = $_POST['sitInId'];
    $lab = $_POST['sit_in_lab'];
    $purpose = $_POST['sit_in_purpose'];
    $date = date("Y-m-d");
    $time = date("H:i:s");

    $stmt = $conn->prepare("INSERT INTO sit_in_requests (idNo, sitin_lab, sitin_date, sitin_time, sitin_purpose) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $idNo, $lab, $date, $time, $purpose);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Sit-in request created for student.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

$studentsQuery = "SELECT idNo, firstName, middleName, lastName, course, yearLevel, sessions FROM users ORDER BY lastName ASC";
$studentsResult = mysqli_query($conn, $studentsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
            <a href="admin_logout.php">Log-out</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="stats-container card p-3 mb-3">
            <h3 class="text-center">Student List</h3>
            <table class="table table-bordered mt-3">
                <thead class="table-light">
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Sessions</th>
                        <th>Reset Session</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = mysqli_fetch_assoc($studentsResult)): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['idNo']) ?></td>
                            <td><?= htmlspecialchars("{$student['lastName']}, {$student['firstName']} {$student['middleName']}") ?></td>
                            <td><?= htmlspecialchars($student['course']) ?></td>
                            <td><?= htmlspecialchars($student['yearLevel']) ?></td>
                            <td><?= htmlspecialchars($student['sessions']) ?></td>
                            <td><a href="admin_dashboard.php?reset_id=<?= $student['idNo'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Reset sessions for this student?');">Reset</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="search-container card" style="max-width: 30%;">
            <h3 class="text-center">Search Students</h3>
            <input type="text" class="form-control mb-3" id="searchQuery" placeholder="Search by ID or Name">
            <button class="btn btn-primary w-100" id="searchBtn">Search</button>
        </div>
    </div>

    <!-- Student Info Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="sitInId" id="sitInId">
                    <div class="modal-header">
                        <h5 class="modal-title">Student Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Name:</strong> <span id="studentName"></span></p>
                        <p><strong>Course:</strong> <span id="studentCourse"></span></p>
                        <p><strong>Year Level:</strong> <span id="studentYear"></span></p>
                        <p><strong>Email:</strong> <span id="studentEmail"></span></p>

                        <div id="sitInForm" class="mt-4 d-none">
                            <label>Lab:</label>
                            <select name="sit_in_lab" class="form-control mb-2" required>
                                <option value="Lab 1">Lab 1</option>
                                <option value="Lab 2">Lab 2</option>
                                <option value="Lab 3">Lab 3</option>
                                <option value="Lab 4">Lab 4</option>
                            </select>
                            <label>Purpose:</label>
                            <select name="sit_in_purpose" class="form-control mb-2" required>
                                <option value="Coding">Coding</option>
                                <option value="Research">Research</option>
                                <option value="Assignment">Assignment</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a id="deleteStudentBtn" class="btn btn-danger">Delete</a>
                        <button type="button" class="btn btn-warning" id="showSitInForm">Sit-in</button>
                        <button type="submit" class="btn btn-success d-none" id="submitSitInBtn" name="sit_in_submit">Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    $("#searchBtn").click(function () {
        const query = $("#searchQuery").val().trim();
        if (query === "") return alert("Please enter a search term.");

        $.ajax({
            url: "search_student.php",
            type: "GET",
            data: { searchQuery: query },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $("#studentName").text(data.fullName);
                    $("#studentCourse").text(data.course);
                    $("#studentYear").text(data.yearLevel);
                    $("#studentEmail").text(data.email);
                    $("#sitInId").val(data.idNo);
                    $("#deleteStudentBtn").attr("href", "admin_dashboard.php?delete_id=" + data.idNo);
                    $("#studentModal").modal("show");
                } else {
                    alert("Student not found!");
                }
            }
        });
    });

    $("#showSitInForm").click(function () {
        $("#sitInForm").removeClass("d-none");
        $("#submitSitInBtn").removeClass("d-none");
        $(this).addClass("d-none");
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
