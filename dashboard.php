<?php
session_start();
require 'db_config.php'; // Connect to database

// Security check: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch medal data sorted by highest gold, then silver, then bronze
$query = "SELECT * FROM medals ORDER BY gold DESC, silver DESC, bronze DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIS - Medal Tally</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background-color: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background-color: #0056b3; color: white; }
        .btn-logout { background-color: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
        .btn-logout:hover { background-color: #c82333; }
    </style>
</head>
<body>

<div class="header">
    <h2>SUKMA Information System</h2>
    <div>
        <span>Logged in as: <strong><?php echo $_SESSION['username']; ?></strong> (<?php echo $_SESSION['role']; ?>)</span>
        
        <a href="events.php" style="background-color: #f8f9fa; color: #333; padding: 8px 15px; text-decoration: none; border-radius: 4px; border: 1px solid #ccc; margin-right: 10px;">Events Schedule</a>

        <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="manage_users.php" style="background-color: #17a2b8; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;">Manage Users</a>
        <?php endif; ?>
        
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="container">
        <h3>Current Medal Tally</h3>
    <table>
        <thead>
            <tr>
                
                <th>State</th>
                <th>Gold</th>
                <th>Silver</th>
                <th>Bronze</th>
                <th>Total</th>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    <th>Action</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            // Loop through the database results and display each row
            while($row = mysqli_fetch_assoc($result)) { 
                $total = $row['gold'] + $row['silver'] + $row['bronze'];
            ?>
            
            <tr>
                
                <td><?php echo $row['state_name']; ?></td>
                <td><?php echo $row['gold']; ?></td>
                <td><?php echo $row['silver']; ?></td>
                <td><?php echo $row['bronze']; ?></td>
                <td><strong><?php echo $total; ?></strong></td>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    <td>
                        <a href="edit_medal.php?id=<?php echo $row['id']; ?>" style="background-color: #ffc107; color: black; padding: 5px 10px; text-decoration: none; border-radius: 4px;">Update</a>
                    </td>
                <?php endif; ?>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>