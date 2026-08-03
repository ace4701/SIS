<?php
require_once 'auth_guard.php';

if ($_SESSION['role'] == 'public') {
    die("Access Denied.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $venue = mysqli_real_escape_string($conn, $_POST['venue']);
    $raw_date = $_POST['event_date'];
    $parsed_date = DateTime::createFromFormat('Y-m-d', $raw_date);

    if (!$parsed_date || $parsed_date->format('Y-m-d') !== $raw_date) {
        die("Access Denied: Invalid date format detected.");
    }
    
    $event_date = mysqli_real_escape_string($conn, $raw_date);

    $insert_query = "INSERT INTO sports_events (event_name, venue, event_date) VALUES ('$event_name', '$venue', '$event_date')";
    
    if (mysqli_query($conn, $insert_query)) {
        header("Location: events.php");
        exit();
    } else {
        $message = "Error saving event to database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Event - SIS</title>
    <style>
        body { font-family: Arial; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 350px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        a.cancel { display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none; }
    </style>
</head>
<body>

<div class="box">
    <h2 style="text-align: center;">Schedule New Event</h2>
    
    <?php if($message != "") echo "<div style='color:red; text-align:center; margin-bottom:10px;'>$message</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>Sport / Event Name</label>
            <input type="text" name="event_name" placeholder="e.g., Aquatics - 100m Freestyle" required>
        </div>
        
        <div class="form-group">
            <label>Venue / Stadium</label>
            <input type="text" name="venue" placeholder="e.g., National Aquatic Centre" required>
        </div>
        
        <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" required>
        </div>
        
        <button type="submit">Save Event</button>
        <a href="events.php" class="cancel">Cancel</a>
    </form>
</div>

</body>
</html>