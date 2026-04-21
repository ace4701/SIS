<?php
session_start();
require 'db_config.php';

// Everyone can view this page, so we only redirect if they aren't logged in at all
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all events, ordered by date so the upcoming ones show first
$query = "SELECT * FROM sports_events ORDER BY event_date ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sports Events - SIS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background-color: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #0056b3; color: white; }
        .btn-add { background-color: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>

<div class="header">
    <h2>Tournament Schedule</h2>
    <div>
        <a href="dashboard.php" style="color: #0056b3; text-decoration: none;">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Official Sports Events</h3>
        
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
            <a href="add_result.php" style="background-color: #ffc107; color: black; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight:bold; margin-right: 10px;">Record Result</a>
            <a href="add_event.php" class="btn-add">+ Add New Event</a>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Event Name</th>
                <th>Venue</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $count = 1;
            if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) { 
                    // Format the date to look nice (e.g., 24 Aug 2026)
                    $formatted_date = date('d M Y', strtotime($row['event_date']));
            ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><strong><?php echo $row['event_name']; ?></strong></td>
                <td><?php echo $row['venue']; ?></td>
                <td><?php echo $formatted_date; ?></td>
            </tr>
            <?php 
                } 
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>No events scheduled yet.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>