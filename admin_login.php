<?php
session_start();
require_once "database.php"; 

if (isset($_POST["submit"])) {
    $adminUsername = trim($_POST["adminUsername"]);
    $adminPassword = $_POST["adminPassword"];

    if (empty($adminUsername) || empty($adminPassword)) {
        $error = "All fields are required!";
    } else {
        $sql = "SELECT * FROM admin WHERE adminUsername = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $adminUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if ($admin && password_verify($adminPassword, $admin['adminPassword'])) {
            $_SESSION["admin"] = [
                "adminIdNo" => $admin["adminIdNo"],
                "adminFirstName" => $admin["adminFirstName"],
                "adminMiddleName" => $admin["adminMiddleName"],
                "adminLastName" => $admin["adminLastName"],
                "adminUsername" => $admin["adminUsername"]
            ];
            echo "<script>
                    alert('Login Successful! Redirecting to Admin Dashboard.');
                    window.location.href = 'admin_dashboard.php';
                  </script>";
            exit();
        } else {
            $error = "Invalid Username or Password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">
    <div class="login-container">
        <img src="ccs.png" alt="Logo" class="logo">
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="admin_login.php" method="post">
            <div class="mb-3">
                <input type="text" class="form-control" name="adminUsername" placeholder="Admin Username" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="adminPassword" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100" name="submit">Login</button>
        </form>
        <p class="mt-3">
            Don't have an account? <a href="admin_register.php">Register here</a>
        </p>
    </div>
</body>
</html>
