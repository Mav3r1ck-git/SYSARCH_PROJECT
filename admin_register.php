<?php
session_start();
require_once "database.php";

if (isset($_POST["register"])) {
    $adminIdNo = trim($_POST["adminIdNo"]);
    $adminFirstName = trim($_POST["adminFirstName"]);
    $adminMiddleName = trim($_POST["adminMiddleName"]);
    $adminLastName = trim($_POST["adminLastName"]);
    $adminUsername = trim($_POST["adminUsername"]);
    $adminPassword = $_POST["adminPassword"];
    $adminConfirmPassword = $_POST["adminConfirmPassword"];

    if (empty($adminIdNo) || empty($adminFirstName) || empty($adminLastName) || empty($adminUsername) || empty($adminPassword) || empty($adminConfirmPassword)) {
        $error = "All fields are required!";
    } elseif ($adminPassword !== $adminConfirmPassword) {
        $error = "Passwords do not match!";
    } else {
        $check_sql = "SELECT * FROM admin WHERE adminIdNo = ? OR adminUsername = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "ss", $adminIdNo, $adminUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $error = "Admin ID or Username already exists!";
        } else {
            $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admin (adminIdNo, adminFirstName, adminMiddleName, adminLastName, adminUsername, adminPassword) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssss", $adminIdNo, $adminFirstName, $adminMiddleName, $adminLastName, $adminUsername, $hashedPassword);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>
                        alert('Admin account created successfully! Redirecting to login.');
                        window.location.href = 'admin_login.php';
                      </script>";
                exit();
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">
    <div class="login-container">
        <h3 class="text-center">Admin Registration</h3>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="admin_register.php" method="post">
            <div class="mb-3">
                <input type="text" class="form-control" name="adminIdNo" placeholder="Admin ID No" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="adminFirstName" placeholder="First Name" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="adminMiddleName" placeholder="Middle Name">
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="adminLastName" placeholder="Last Name" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="adminUsername" placeholder="Admin Username" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="adminPassword" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="adminConfirmPassword" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100" name="register">Register</button>
        </form>
        <p class="mt-3">
            Already have an account? <a href="admin_login.php">Login here</a>
        </p>
    </div>
</body>
</html>
