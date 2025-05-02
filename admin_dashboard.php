<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit();
}

// Actions
if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE idNo = ?");
    $stmt->bind_param("i", $_GET['delete_id']);
    $stmt->execute();
    echo "<script>alert('Student deleted.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

if (isset($_GET['reset_id'])) {
    $stmt = $conn->prepare("UPDATE users SET sessions = 30 WHERE idNo = ?");
    $stmt->bind_param("i", $_GET['reset_id']);
    $stmt->execute();
    echo "<script>alert('Sessions reset.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

if (isset($_GET['reset_all'])) {
    $conn->query("UPDATE users SET sessions = 30");
    echo "<script>alert('All sessions reset.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

if (isset($_GET['reward_id'])) {
    $idNo = $_GET['reward_id'];
    $stmt = $conn->prepare("SELECT points, sessions FROM users WHERE idNo = ?");
    $stmt->bind_param("i", $idNo);
    $stmt->execute();
    $stmt->bind_result($points, $sessions);
    $stmt->fetch();
    $stmt->close();

    $newPoints = $points + 1;
    $addSessions = floor($newPoints / 3) - floor($points / 3);
    $newSessions = $sessions + $addSessions;

    $stmt = $conn->prepare("UPDATE users SET points = ?, sessions = ? WHERE idNo = ?");
    $stmt->bind_param("iii", $newPoints, $newSessions, $idNo);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('1 point rewarded.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

if (isset($_POST['update_student'])) {
    $stmt = $conn->prepare("UPDATE users SET firstName=?, middleName=?, lastName=?, course=?, yearLevel=?, sessions=? WHERE idNo=?");
    $stmt->bind_param("ssssssi", $_POST['edit_firstname'], $_POST['edit_middlename'], $_POST['edit_lastname'], $_POST['edit_course'], $_POST['edit_year'], $_POST['edit_sessions'], $_POST['edit_id']);
    $stmt->execute();
    echo "<script>alert('Student updated.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

$result = $conn->query("SELECT idNo, firstName, middleName, lastName, course, yearLevel, sessions, COALESCE(points, 0) as points, COALESCE(sitin_count, 0) as sitin_count FROM users ORDER BY lastName ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
                    <th>Points</th>
                    <th>Sit-ins</th>
                    <th>
                        Action
                        <a href="?reset_all=1" class="btn btn-sm btn-danger ms-2" onclick="return confirm('Reset all sessions?')">Reset All</a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['idNo']) ?></td>
                        <td><?= htmlspecialchars("{$row['lastName']}, {$row['firstName']} {$row['middleName']}") ?></td>
                        <td><?= htmlspecialchars($row['course']) ?></td>
                        <td><?= htmlspecialchars($row['yearLevel']) ?></td>
                        <td><?= htmlspecialchars($row['sessions']) ?></td>
                        <td><?= htmlspecialchars($row['points']) ?></td>
                        <td><?= htmlspecialchars($row['sitin_count']) ?></td>
                        <td>
                            <a href="?reset_id=<?= $row['idNo'] ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                            <a href="?delete_id=<?= $row['idNo'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                            <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="search-container card p-3" style="max-width: 30%;">
        <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#leaderboardModal">
            <i class="fas fa-trophy"></i> View Leaderboard
        </button>
        <a href="admin_upload_resources.php" class="btn btn-success w-100 mb-3">
            <i class="fas fa-file-upload"></i> Resources
        </a>
        <h3 class="text-center">Search Students</h3>
        <input type="text" class="form-control mb-3" id="searchQuery" placeholder="Search by ID or Name">
        <button class="btn btn-primary w-100" id="searchBtn">Search</button>
        <div id="searchResult" class="mt-3 d-none">
            <p><strong>ID:</strong> <span id="s_id"></span></p>
            <p><strong>Name:</strong> <span id="s_name"></span></p>
            <p><strong>Course:</strong> <span id="s_course"></span></p>
            <p><strong>Year:</strong> <span id="s_year"></span></p>
            <p><strong>Email:</strong> <span id="s_email"></span></p>
            <div class="text-center">
                <a href="#" id="btnEdit" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="#" id="btnReset" class="btn btn-sm btn-outline-secondary">Reset</a>
                <a href="#" id="btnDelete" class="btn btn-sm btn-outline-danger">Delete</a>
                <button type="button" id="btnSitIn" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#sitInModal">Sit-in</button>
            </div>
        </div>
    </div>
</div>

<!-- Leaderboard Modal -->
<div class="modal fade" id="leaderboardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Leaderboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $leaderboardQuery = "SELECT idNo, firstName, middleName, lastName, course, yearLevel, 
                                           COALESCE(points, 0) as points, 
                                           COALESCE(sitin_count, 0) as sitin_count,
                                           (COALESCE(points, 0) + COALESCE(sitin_count, 0)) as total_score
                                           FROM users 
                                           ORDER BY total_score DESC, lastName ASC";
                        $leaderboardResult = $conn->query($leaderboardQuery);
                        $rank = 1;
                        while ($row = $leaderboardResult->fetch_assoc()):
                            $fullName = htmlspecialchars("{$row['lastName']}, {$row['firstName']} " . substr($row['middleName'], 0, 1) . ".");
                        ?>
                            <tr>
                                <td><?= $rank ?></td>
                                <td><?= $fullName ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <td><?= htmlspecialchars($row['yearLevel']) ?></td>
                                <td><?= $row['total_score'] ?></td>
                            </tr>
                        <?php 
                            $rank++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- SIT-IN MODAL -->
<div class="modal fade" id="sitInModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="submit_sitin.php" class="modal-content">
            <input type="hidden" name="student_id" id="sitInStudentId">
            <input type="hidden" name="sitin_date" value="<?= date('Y-m-d') ?>">
            <input type="hidden" name="sitin_time" value="<?= date('H:i') ?>">
            <div class="modal-header">
                <h5 class="modal-title">Sit-in Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Lab</label>
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
                    <label class="form-label">PC Number</label>
                    <select name="pc_number" class="form-control" required>
                        <option value="" disabled selected>Select PC</option>
                        <?php for ($i = 1; $i <= 50; $i++): ?>
                            <option value="PC<?= $i ?>">PC<?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purpose</label>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="submit_request" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" name="edit_firstname" id="edit_firstname" class="form-control mb-2" placeholder="First Name" required>
                <input type="text" name="edit_middlename" id="edit_middlename" class="form-control mb-2" placeholder="Middle Name">
                <input type="text" name="edit_lastname" id="edit_lastname" class="form-control mb-2" placeholder="Last Name" required>
                <input type="text" name="edit_course" id="edit_course" class="form-control mb-2" placeholder="Course" required>
                <input type="text" name="edit_year" id="edit_year" class="form-control mb-2" placeholder="Year Level" required>
                <input type="number" name="edit_sessions" id="edit_sessions" class="form-control mb-2" placeholder="Sessions" required>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_student" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleOtherPurpose(select) {
    const input = select.nextElementSibling;
    if (select.value === 'Others') {
        input.classList.remove('d-none');
        input.required = true;
    } else {
        input.classList.add('d-none');
        input.required = false;
    }
}
</script>

<script>
function openEditModal(data) {
    document.getElementById("edit_id").value = data.idNo;
    document.getElementById("edit_firstname").value = data.firstName;
    document.getElementById("edit_middlename").value = data.middleName;
    document.getElementById("edit_lastname").value = data.lastName;
    document.getElementById("edit_course").value = data.course;
    document.getElementById("edit_year").value = data.yearLevel;
    document.getElementById("edit_sessions").value = data.sessions;
    new bootstrap.Modal(document.getElementById("editModal")).show();
}

document.getElementById("searchBtn").addEventListener("click", function () {
    const query = document.getElementById("searchQuery").value.trim();
    if (!query) return alert("Enter ID or name");

    fetch(`search_student.php?searchQuery=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("s_id").textContent = data.idNo;
                document.getElementById("s_name").textContent = data.fullName;
                document.getElementById("s_course").textContent = data.course;
                document.getElementById("s_year").textContent = data.yearLevel;
                document.getElementById("s_email").textContent = data.email;
                document.getElementById("btnReset").href = `?reset_id=${data.idNo}`;
                document.getElementById("btnDelete").href = `?delete_id=${data.idNo}`;
                document.getElementById("sitInStudentId").value = data.idNo;
                document.getElementById("btnEdit").onclick = function () {
                    openEditModal({
                        idNo: data.idNo,
                        firstName: data.fullName.split(" ")[0], // Basic parse
                        middleName: "", // if needed, enhance with full data from server
                        lastName: data.fullName.split(" ")[2] || "",
                        course: data.course,
                        yearLevel: data.yearLevel,
                        sessions: 30 // Default or fetched
                    });
                };  
                document.getElementById("searchResult").classList.remove("d-none");
            } else {
                alert("Student not found.");
                document.getElementById("searchResult").classList.add("d-none");
            }
        });
});
</script>

<script>
document.querySelector('select[name="sitin_purpose"]').addEventListener('change', function() {
    const otherPurposeDiv = document.getElementById('otherPurposeDiv');
    otherPurposeDiv.style.display = this.value === 'Others' ? 'block' : 'none';
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
