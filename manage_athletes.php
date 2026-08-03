<?php
session_start();
require 'db_config.php';

// Security check - Only Admin and Staff can manage athletes
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    header("Location: dashboard.php");
    exit();
}

$msg = "";
$all_states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 
    'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 
    'Terengganu', 'Wilayah Persekutuan'
];

// --- 1. HANDLE ADD ATHLETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = mysqli_real_escape_string($conn, strtoupper($_POST['full_name'])); // Uppercase for consistency
    $state = mysqli_real_escape_string($conn, $_POST['contingent_state']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);

    $insert = "INSERT INTO athletes (full_name, contingent_state, gender) VALUES ('$name', '$state', '$gender')";
    if (mysqli_query($conn, $insert)) {
        $msg = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>✅ Athlete '$name' successfully registered!</div>";
    } else {
        $msg = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>❌ Error saving to database.</div>";
    }
}

// --- 2. HANDLE DELETE ATHLETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['athlete_id'];
    
    // Deleting the athlete will automatically delete their event links because of 'ON DELETE CASCADE' in our SQL!
    if (mysqli_query($conn, "DELETE FROM athletes WHERE id = $id")) {
        $msg = "<div style='color: white; background: #17a2b8; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>🗑️ Athlete removed from the system.</div>";
    }
}

// --- 3. FETCH ALL ATHLETES ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = "";
if ($search != '') {
    $where_clause = "WHERE full_name LIKE '%$search%' OR contingent_state LIKE '%$search%' ";
}

$athletes_query = mysqli_query($conn, "SELECT * FROM athletes $where_clause ORDER BY contingent_state ASC, gender ASC, full_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIS - Manage Athletes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #f4f6f9;">

<div class="top-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="assets/sukma_logo.png" alt="Logo" style="height: 40px;" onerror="this.style.display='none'">
        <h2>SUKMA Registration System</h2>
    </div>
    <div class="user-controls">
        <a href="dashboard.php" style="background: #343a40; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">⬅️ Back to Dashboard</a>
    </div>
</div>

<div class="tab-content" style="display: block;">
    <div class="dashboard-wrapper">
        
        <!-- LEFT PANEL: ADD ATHLETE FORM -->
        <div class="side-panel" style="flex: 1; min-width: 300px;">
            <div class="generic-container" style="position: sticky; top: 20px;">
                <h3 style="color: #da251d; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0;">Register New Athlete</h3>
                
                <?php echo $msg; ?>
                
                <form method="POST" action="manage_athletes.php">
                    <input type="hidden" name="action" value="add">
                    
                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 15px;">Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g., AHMAD BIN ABU" style="width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    
                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 10px;">Contingent / State</label>
                    <select name="contingent_state" required style="width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="" disabled selected>-- Select State --</option>
                        <?php foreach($all_states as $s): ?>
                            <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 10px;">Gender Category</label>
                    <select name="gender" required style="width: 100%; padding: 10px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="" disabled selected>-- Select Gender --</option>
                        <option value="Male">Male (Lelaki)</option>
                        <option value="Female">Female (Wanita)</option>
                        <option value="Mixed">Mixed (Campuran)</option>
                    </select>
                    
                    <button type="submit" style="width: 100%; background: #28a745; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 15px;">💾 Register Athlete</button>
                </form>
            </div>
        </div>

        <!-- RIGHT PANEL: ATHLETE MASTER LIST -->
        <div class="center-panel" style="flex: 2;">
            <div class="generic-container" style="padding: 0; overflow: hidden;">
                
                <div style="padding: 20px; background: white; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Athlete Master List (<?php echo mysqli_num_rows($athletes_query); ?>)</h3>
                    
                    <form method="GET" action="manage_athletes.php" style="margin: 0; display: flex; gap: 10px;">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="🔍 Search Name or State..." style="padding: 8px 15px; border: 1px solid #ccc; border-radius: 20px; outline: none; font-size: 14px; width: 250px;">
                        <?php if($search != ''): ?>
                            <a href="manage_athletes.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 20px; font-size: 14px;">Clear</a>
                        <?php else: ?>
                            <button type="submit" style="padding: 8px 15px; background: #0056b3; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 14px;">Search</button>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="max-height: 600px; overflow-y: auto;">
                    <table style="margin: 0; width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <tr>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">No.</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Athlete Name</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">State</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">Gender</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if(mysqli_num_rows($athletes_query) > 0) {
                                $count = 1;
                                while($row = mysqli_fetch_assoc($athletes_query)) { 
                                    $img = strtolower(str_replace(' ', '_', $row['contingent_state'])) . '.png';
                            ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;" onmouseover="this.style.background='#fcfcfc'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 12px; text-align: center; color: #777; font-size: 14px;"><?php echo $count++; ?></td>
                                    
                                    <td style="padding: 12px; font-weight: bold; color: #333; font-size: 14px;">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                            <img src="assets/flags/<?php echo $img; ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;" alt="flag">
                                            <span style="font-size: 13px; font-weight: bold; color: #555;"><?php echo strtoupper(substr($row['contingent_state'], 0, 3)); ?></span>
                                        </div>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">
                                        <?php 
                                        if($row['gender'] == 'Male') echo "👨 Lelaki";
                                        if($row['gender'] == 'Female') echo "👩 Wanita";
                                        if($row['gender'] == 'Mixed') echo "🚻 Campuran";
                                        ?>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center;">
                                        <form method="POST" action="manage_athletes.php" onsubmit="return confirm('Are you sure you want to delete this athlete? They will be removed from all assigned matches.');" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="athlete_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Delete Athlete">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } } else { ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #777;">No athletes found. Register someone to get started!</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

</body>
</html>