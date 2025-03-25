<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION["user"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST["firstName"]);
    $middleName = trim($_POST["middleName"]);
    $lastName = trim($_POST["lastName"]);
    $course = trim($_POST["course"]);
    $yearLevel = trim($_POST["yearLevel"]);
    $email = trim($_POST["emailAddress"]);

    $sql = "UPDATE users SET firstName=?, middleName=?, lastName=?, course=?, yearLevel=?, emailAddress=? WHERE idNo=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $firstName, $middleName, $lastName, $course, $yearLevel, $email, $user["idNo"]);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION["user"]["firstName"] = $firstName;
        $_SESSION["user"]["middleName"] = $middleName;
        $_SESSION["user"]["lastName"] = $lastName;
        $_SESSION["user"]["course"] = $course;
        $_SESSION["user"]["yearLevel"] = $yearLevel;
        $_SESSION["user"]["emailAddress"] = $email;
        $success = "Profile updated successfully!";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body class="edit-profile-body">
    <div class="header-container">
        <div class="logo-title">
            <h2><img src="ccs.png" alt="Logo" class="logo"> Edit Profile</h2>
        </div>
        <div class="nav-bar">
            <a href="dashboard.php">Home</a>
            <a href="editProfile.php">Edit Profile</a>
            <a href="sitInHistory.php">Sit-in History</a>
            <a href="reservation.php">Reservation</a>
            <a href="login.php">Log-out</a>
        </div>
    </div>

    <div class="edit-profile-container card">
        <h3 class="text-center">Update Your Profile</h3>

        <?php if (isset($success)) : ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <center><img src="profilepicture.png" alt="Profile Picture" class="profile-img"></center>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstName" class="form-control" value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
            </div>

            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middleName" class="form-control" value="<?php echo htmlspecialchars($user['middleName']); ?>" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastName" class="form-control" value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
            </div>

            <div class="form-group">
                <label>Course</label>
                <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($user['course']); ?>" required>
            </div>

            <div class="form-group">
                <label>Year Level</label>
                <input type="text" name="yearLevel" class="form-control" value="<?php echo htmlspecialchars($user['yearLevel']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="emailAddress" class="form-control" value="<?php echo htmlspecialchars($user['emailAddress']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
    </div>
</body>
</html>
