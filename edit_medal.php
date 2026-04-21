<?php
session_start();
require 'db_config.php';

// Security: Kick out unlogged users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// NEW SECURITY CHECK: Kick out public users
if ($_SESSION['role'] == 'public') {
    die("Access Denied: You do not have permission to modify the medal tally.");
    // Alternatively, redirect them: header("Location: dashboard.php"); exit();
}   

$message = "";

// 1. Handle the form submission (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $gold = (int)$_POST['gold'];
    $silver = (int)$_POST['silver'];
    $bronze = (int)$_POST['bronze'];

    $update_query = "UPDATE medals SET gold = '$gold', silver = '$silver', bronze = '$bronze' WHERE id = '$id'";
    
    if (mysqli_query($conn, $update_query)) {
        // Teleport back to dashboard to see the updated rankings
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "Error updating database.";
    }
}

// 2. Fetch current data to display in the form (READ)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT * FROM medals WHERE id = '$id'";
$result = mysqli_query($conn, $query);
$state_data = mysqli_fetch_assoc($result);

if (!$state_data) {
    die("State not found in database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Medals</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-bottom: 10px; }
        .btn:hover { background-color: #218838; }
        .btn-cancel { background-color: #6c757d; text-align: center; display: block; text-decoration: none; padding: 10px; color: white; border-radius: 4px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Update: <?php echo $state_data['state_name']; ?></h2>
    
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $state_data['id']; ?>">
        
        <div class="form-group">
            <label>Gold Medals</label>
            <input type="number" name="gold" value="<?php echo $state_data['gold']; ?>" min="0">
        </div>
        <div class="form-group">
            <label>Silver Medals</label>
            <input type="number" name="silver" value="<?php echo $state_data['silver']; ?>" min="0">
        </div>
        <div class="form-group">
            <label>Bronze Medals</label>
            <input type="number" name="bronze" value="<?php echo $state_data['bronze']; ?>" min="0">
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
        <a href="dashboard.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>