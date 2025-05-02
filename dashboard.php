<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];
$fullName = "{$user['firstName']} " . substr($user['middleName'], 0, 1) . ". {$user['lastName']}";

$idNo = $user['idNo'];
$query = "SELECT sessions FROM users WHERE idNo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idNo);
$stmt->execute();
$stmt->bind_result($remainingSessions);
$stmt->fetch();
$stmt->close();

$announcementQuery = "SELECT announcement_content, announcement_date FROM announcement ORDER BY announcement_date DESC";
$announcementResult = $conn->query($announcementQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .scroll-box {
            max-height: 400px; 
            overflow-y: auto;
        }

        .equal-height {
            display: flex;
            align-items: stretch;
        }
    </style>
</head>

<body class="dashboard-body">
    <div class="header-container">
        <h2><img src="ccs.png" alt="Logo" class="logo"> Dashboard</h2>
        <div class="nav-bar">
            <a href="dashboard.php">Home</a>
            <a href="editProfile.php">Edit Profile</a>
            <a href="sitInHistory.php">Sit-in History</a>
            <a href="reservation.php">Reservation</a>
            <a href="user_resources.php">Resources</a>
            <a href="login.php">Log-out</a>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row equal-height">
            <!-- Left Column (Profile) -->
            <div class="col-md-3">
                <div class="card p-3 h-100">
                    <h3><center>Profile</center></h3>
                    <center><img src="profilepicture.png" alt="Profile Picture" class="profile-img"></center>
                    <p><strong>Name:</strong> <?php echo $fullName; ?></p>
                    <p><strong>ID Number:</strong> <?php echo $user['idNo']; ?></p>
                    <p><strong>Course:</strong> <?php echo $user['course']; ?></p>
                    <p><strong>Year Level:</strong> <?php echo $user['yearLevel']; ?></p>
                    <p><strong>Email:</strong> <?php echo $user['emailAddress']; ?></p>
                    <p><strong>Sessions Remaining:</strong> <?php echo $remainingSessions; ?>/30</p>
                </div>
            </div>

            <!-- Middle Column (Announcements) -->
            <div class="col-md-3">
                <div class="card p-3 h-100">
                    <h3 class="text-center">Announcements</h3>
                    <div class="scroll-box">
                        <?php while ($row = $announcementResult->fetch_assoc()) : ?>
                            <div class="announcement-item mb-3">
                                <p class="fw-bold text-dark">
                                    <?php echo date("F j, Y - h:i A", strtotime($row['announcement_date'])); ?>
                                </p>
                                <p class="text-dark"><?php echo nl2br(htmlspecialchars($row['announcement_content'])); ?></p>
                                <hr>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Third Column (Leaderboard) -->
            <div class="col-md-3">
                <div class="card p-3 h-100">
                    <h3 class="text-center">Leaderboard</h3>
                    <div class="scroll-box">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Name</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $leaderboardQuery = "SELECT idNo, firstName, middleName, lastName, 
                                                   COALESCE(points, 0) as points, 
                                                   COALESCE(sitin_count, 0) as sitin_count,
                                                   (COALESCE(points, 0) + COALESCE(sitin_count, 0)) as total_score
                                                   FROM users 
                                                   ORDER BY total_score DESC, lastName ASC 
                                                   LIMIT 10";
                                $leaderboardResult = $conn->query($leaderboardQuery);
                                $rank = 1;
                                while ($row = $leaderboardResult->fetch_assoc()):
                                    $fullName = htmlspecialchars("{$row['lastName']}, {$row['firstName']} " . substr($row['middleName'], 0, 1) . ".");
                                ?>
                                    <tr>
                                        <td><?= $rank ?></td>
                                        <td><?= $fullName ?></td>
                                        <td><?= $row['total_score'] ?></td>
                                    </tr>
                                <?php 
                                    $rank++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column (Rules & Regulations) -->
            <div class="col-md-3">
                <div class="card p-3 h-100">
                    <h3 class="text-center">Rules & Regulations</h3>
                    <div class="scroll-box">
                        <h5 class="text-center fw-bold">University of Cebu - College of Information & Computer Studies</h5>
                        <p><strong>Laboratory Rules and Regulations</strong></p>
                        <ul>
                            <li>Maintain silence, proper decorum, and discipline inside the lab.</li>
                            <li>Games, unauthorized surfing, and software installations are not allowed.</li>
                            <li>Accessing illicit websites is strictly prohibited.</li>
                            <li>Deleting files or modifying computer settings is a major offense.</li>
                            <li>Observe computer usage time. Exceeding limits will result in loss of access.</li>
                            <li>Follow seating arrangements and return chairs properly.</li>
                            <li>No eating, drinking, smoking, or vandalism inside the lab.</li>
                            <li>Disruptive behavior may result in being asked to leave.</li>
                            <li>For serious offenses, security personnel may be called.</li>
                            <li>Report technical issues to lab supervisors immediately.</li>
                        </ul>
                        <p><strong>DISCIPLINARY ACTION</strong></p>
                        <ul>
                            <li><strong>First Offense:</strong> Warning or possible suspension.</li>
                            <li><strong>Second Offense:</strong> Heavier disciplinary action.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
