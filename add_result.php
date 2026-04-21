<?php
session_start();
require 'db_config.php';

// Strict Authorization: Only staff/admin can award medals
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'public') {
    die("Access Denied.");
}

$message = "";

// Handle the Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = (int)$_POST['event_id'];
    $state_name = mysqli_real_escape_string($conn, $_POST['state_name']);
    $medal_color = mysqli_real_escape_string($conn, $_POST['medal_color']);

    // 1. Insert the "Receipt" into event_results
    $insert_query = "INSERT INTO event_results (event_id, state_name, medal_color) VALUES ('$event_id', '$state_name', '$medal_color')";
    
    if (mysqli_query($conn, $insert_query)) {
        // 2. Automatically update the main dashboard tally!
        $update_query = "UPDATE medals SET $medal_color = $medal_color + 1 WHERE state_name = '$state_name'";
        mysqli_query($conn, $update_query);
        
        $message = "<div style='color: green; text-align: center; margin-bottom: 15px;'>Result successfully recorded and tally updated!</div>";
    } else {
        $message = "<div style='color: red; text-align: center; margin-bottom: 15px;'>Error recording result.</div>";
    }
}

// Fetch data for the dropdown menus
$events_result = mysqli_query($conn, "SELECT id, event_name FROM sports_events ORDER BY event_date ASC");
$states_result = mysqli_query($conn, "SELECT state_name FROM medals ORDER BY state_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Award Medal - SIS</title>
    <style>
        body { font-family: Arial; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 400px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #ffc107; color: black; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px; }
        button:hover { background: #e0a800; }
        a.cancel { display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align: center;">Record Event Result</h2>
    
    <?php echo $message; ?>

    <form method="POST">
        <div class="form-group">
            <label>Select Event</label>
            <select name="event_id" required>
                <option value="">-- Choose Event --</option>
                <?php while($event = mysqli_fetch_assoc($events_result)): ?>
                    <option value="<?php echo $event['id']; ?>"><?php echo $event['event_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Winning State</label>
            <select name="state_name" required>
                <option value="">-- Choose State --</option>
                <?php while($state = mysqli_fetch_assoc($states_result)): ?>
                    <option value="<?php echo $state['state_name']; ?>"><?php echo $state['state_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Medal Awarded</label>
            <select name="medal_color" required>
                <option value="">-- Choose Medal --</option>
                <option value="gold">Gold</option>
                <option value="silver">Silver</option>
                <option value="bronze">Bronze</option>
            </select>
        </div>
        
        <button type="submit">Award Medal</button>
        <a href="dashboard.php" class="cancel">Return to Dashboard</a>
    </form>
</div>

</body>
</html>