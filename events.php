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
                    <th>Event Name</th>
                    <th>Venue</th>
                    <th>Participants</th> <th class="center">Date</th>
                </tr>
            </thead>
            <tbody id="events-table-body">
                <?php 
                mysqli_data_seek($events_result, 0);
                while($event = mysqli_fetch_assoc($events_result)) { 
                    
                    // Decode the JSON array from the database
                    $participants_text = "<span style='color:#aaa;'>TBD</span>";
                    if (!empty($event['participating_states'])) {
                        $states_array = json_decode($event['participating_states'], true);
                        
                        if (is_array($states_array) && count($states_array) > 0) {
                            if (count($states_array) == 2) {
                                // 1v1 Format styling
                                $participants_text = "<span style='color:#da251d; font-weight:bold;'>" . $states_array[0] . "</span> <span style='font-size:11px; color:#777; margin:0 5px;'>vs</span> <span style='color:#0056b3; font-weight:bold;'>" . $states_array[1] . "</span>";
                            } else {
                                // Group Format styling
                                $participants_text = implode(', ', $states_array);
                            }
                        }
                    }
                ?>
                <tr>
                    <td><strong><?php echo $event['event_name']; ?></strong></td>
                    <td><?php echo $event['venue']; ?></td>
                    <td style="font-size: 14px; line-height: 1.5;"><?php echo $participants_text; ?></td> <td class="center"><?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
</div>

</body>
</html>