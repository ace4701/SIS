<?php
session_start();
require 'db_config.php';

$step = 1; // Determines which form to show
$message = "";
$user_id_to_reset = null;

// Handle Step 1: Verify Username and Email
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $query = "SELECT id FROM users WHERE username = '$username' AND email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $step = 2; // Identity verified, move to password reset form
        $user_id_to_reset = $row['id'];
    } else {
        $message = "Error: No matching account found with that Username and Email combination.";
    }
}

// Handle Step 2: Update the Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = (int)$_POST['user_id'];

    if ($new_password !== $confirm_password) {
        $message = "Error: Passwords do not match.";
        $step = 2; // Keep them on the reset form
        $user_id_to_reset = $user_id;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $update_query)) {
            $step = 3; // Success state
        } else {
            $message = "Error: Database update failed.";
            $step = 2;
            $user_id_to_reset = $user_id;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - SUKMA</title>
    <style>
        body { font-family: Arial; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px;}
        button:hover { background: #004494; }
        .msg { color: red; text-align: center; font-size: 14px; margin-bottom: 10px;}
        .success { color: #28a745; text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px;}
        a { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px;}
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align: center;">Account Recovery</h2>
    
    <?php if($message != ""): ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <p style="font-size: 14px; color: #555; text-align: center;">Enter your account details to verify your identity.</p>
        <form method="POST">
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="email" name="email" placeholder="Enter Account Email" required>
            <button type="submit" name="verify_user">Verify Identity</button>
        </form>

    <?php elseif ($step == 2): ?>
        <p style="font-size: 14px; color: #555; text-align: center;">Identity verified. Please create a new password.</p>
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $user_id_to_reset; ?>">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="reset_password" style="background: #28a745;">Save New Password</button>
        </form>

    <?php elseif ($step == 3): ?>
        <div class="success">Password Reset Successfully!</div>
        <a href="login.php" style="background: #0056b3; color: white; padding: 10px; border-radius: 4px; text-decoration: none;">Return to Login</a>
    <?php endif; ?>

    <?php if ($step != 3): ?>
        <a href="login.php">Cancel and return to Login</a>
    <?php endif; ?>
</div>

</body>
</html>