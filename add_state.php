<?php
session_start();
require 'db_config.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize the input
    $state_name = mysqli_real_escape_string($conn, $_POST['state_name']);

    // Check if the state already exists to prevent duplicate errors
    $check_query = "SELECT * FROM medals WHERE state_name = '$state_name'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "Error: This state is already on the board.";
    } else {
        // Insert the new state. Medals default to 0 automatically based on our database design.
        $insert_query = "INSERT INTO medals (state_name) VALUES ('$state_name')";
        
        if (mysqli_query($conn, $insert_query)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Error adding state to database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New State</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-bottom: 10px; }
        .btn:hover { background-color: #004494; }
        .btn-cancel { background-color: #6c757d; text-align: center; display: block; text-decoration: none; padding: 10px; color: white; border-radius: 4px; }
        .error { color: red; margin-bottom: 10px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add New State</h2>
    
    <?php if($message != ""): ?>
        <div class="error"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>State Name</label>
            <input type="text" name="state_name" required placeholder="e.g., Sabah">
        </div>
        
        <button type="submit" class="btn">Add State</button>
        <a href="dashboard.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>