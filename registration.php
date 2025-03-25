<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="registration-body">
    <div class="logo-container">
    <div class="registration-container">
        <img src="ccs2.png" alt="Logo" class="logo">
        <?php
        if(isset($_POST["submit"])){
            require_once "database.php";

            $userName = trim($_POST["userName"]);
            $password = $_POST["password"];
            $idNo = trim($_POST["idNo"]);
            $lastName = trim($_POST["lastName"]);
            $firstName = trim($_POST["firstName"]);
            $middleName = trim($_POST["middleName"]);
            $course = trim($_POST["course"]);
            $yearLevel = trim($_POST["yearLevel"]);
            $emailAddress = trim($_POST["emailAddress"]);

            $errors = array();
            
            if (empty($userName) || empty($password) || empty($idNo) || empty($lastName) || empty($firstName) || empty($middleName) || empty($course) || empty($yearLevel) || empty($emailAddress)){
                array_push($errors, "All fields are required.");
            }
            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)){
                array_push($errors, "Invalid email format.");
            }
            if (strlen($password) < 8){
                array_push($errors, "Password must be at least 8 characters.");
            }
            if(count($errors) > 0){
                foreach($errors as $error){
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (userName, password, idNo, lastName, firstName, middleName, course, yearLevel, emailAddress) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_stmt_init($conn);

                if(mysqli_stmt_prepare($stmt, $sql)){
                    mysqli_stmt_bind_param($stmt, "ssissssis", $userName, $hashedPassword, $idNo, $lastName, $firstName, $middleName, $course, $yearLevel, $emailAddress);
                    mysqli_stmt_execute($stmt);

                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);

                    echo "<script>
                            alert('REGISTERED SUCCESSFULLY! Returning to login page.');
                            window.location.href = 'login.php';
                          </script>";
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Something went wrong. Please try again.</div>";
                }
            }
        }
        ?>
        <form action="registration.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="userName" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="idNo" placeholder="ID Number" required>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="lastName" placeholder="Last Name" required>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="firstName" placeholder="First Name" required>
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="middleName" placeholder="Middle Name" required>
            </div>
            <div class="form-group">
                <select id="course" class="form-control" name="course" required>
                    <option value="" disabled selected>Select your course</option>
                    <option value="BSA">Bachelor of Science in Accountancy (BSA)</option>
                    <option value="BAA">Bachelor of Arts (BAA)</option>
                    <option value="BSEd-BioSci">BSEd Major in Biological Science (BSEd-BioSci)</option>
                    <option value="BSBA">Bachelor of Science in Business Administration (BSBA)</option>
                    <option value="BSCE">Bachelor of Science in Civil Engineering (BSCE)</option>
                    <option value="BSCpE">Bachelor of Science in Computer Engineering (BSCpE)</option>
                    <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                    <option value="BSEE">Bachelor of Science in Electrical Engineering (BSEE)</option>
                    <option value="BSECE">Bachelor of Science in Electronics and Communication Engineering (BSECE)</option>
                    <option value="BSME">Bachelor of Science in Mechanical Engineering (BSME)</option>
                    <option value="BSOA">Bachelor of Science in Office Administration (BSOA)</option>
                    <option value="BSREM">Bachelor of Science in Real Estate Management (BSREM)</option>
                    <option value="BSCS">Bachelor of Science in Computer Studies(BSCS)</option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                </select>
            </div>
            <div class="form-group">
                <select id="yearLevel" class="form-control" name="yearLevel" required>
                    <option value="" disabled selected>Select year level</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="emailAddress" placeholder="Email Address" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
    </div>
    </div>
</body>
</html>
