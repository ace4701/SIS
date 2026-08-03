<?php
session_start();
require 'db_config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // 2. Check if username or email already exists using a Prepared Statement
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $message = "Username or Email already taken.";
            } else {
                // 3. Hash the password and insert the new PUBLIC user using a Prepared Statement
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'public'; // Default role for self-registration

                $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                
                if ($insert_stmt) {
                    mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $hashed_password, $role);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $message = "Registration successful! You can now login.";
                    } else {
                        $message = "Error: Could not register user.";
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $message = "System error during registration.";
                }
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $message = "System database error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SUKMA</title>
    <style>
        body { font-family: Arial; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .msg { color: red; text-align: center; font-size: 14px;}
        .success { color: green; text-align: center; font-size: 14px;}
        a { display: block; text-align: center; margin-top: 10px; color: #0056b3; text-decoration: none; font-size: 14px;}
    </style>
</head>
<body>
<div class="box">
    <h2 style="text-align: center;">Register Account</h2>
    <?php if($message != ""): ?>
        <div class="<?php echo strpos($message, 'successful') !== false ? 'success' : 'msg'; ?>"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
    <a href="login.php">Back to Login</a>
</div>
</body>
</html>