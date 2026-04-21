<?php
session_start();
require 'db_config.php';

// Strict Authorization: ONLY the admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";

// 1. Handle Account Creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']); // Role comes from a secure dropdown

    // Check for duplicates
    $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = "<div style='color: red; margin-bottom: 15px;'>Error: Username or Email already exists.</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";
        
        if (mysqli_query($conn, $insert_query)) {
            $message = "<div style='color: green; margin-bottom: 15px;'>Success: Account for $username created!</div>";
        } else {
            $message = "<div style='color: red; margin-bottom: 15px;'>Error: Database update failed.</div>";
        }
    }
}

// 2. Fetch all users to display in the table
$users_query = "SELECT id, username, email, role, created_at FROM users ORDER BY role ASC, created_at DESC";
$users_result = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - SIS</title>
    <style>
        body { font-family: Arial; background-color: #f4f7f6; padding: 20px; }
        .container { display: flex; gap: 20px; max-width: 1000px; margin: 0 auto; }
        .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-panel { flex: 1; }
        .table-panel { flex: 2; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;}
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #343a40; color: white; }
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
        .role-admin { background-color: #dc3545; }
        .role-staff { background-color: #17a2b8; }
        .role-public { background-color: #6c757d; }
    </style>
</head>
<body>

<h2 style="text-align: center; color: #333;">Secretariat Administration</h2>
<div style="text-align: center; margin-bottom: 20px;">
    <a href="dashboard.php" style="color: #0056b3; text-decoration: none;">&larr; Back to Dashboard</a>
</div>

<div class="container">
    <div class="panel form-panel">
        <h3>Provision New Account</h3>
        <?php echo $message; ?>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" required>
            
            <label>Email</label>
            <input type="email" name="email" required>
            
            <label>Temporary Password</label>
            <input type="password" name="password" required>
            
            <label>Assign System Role</label>
            <select name="role" required>
                <option value="staff">Staff / Technical Official</option>
                <option value="admin">System Administrator</option>
            </select>
            
            <button type="submit">Create Official Account</button>
        </form>
    </div>

    <div class="panel table-panel">
        <h3>System Access List</h3>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                <tr>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['email'] ? $user['email'] : 'N/A'; ?></td>
                    <td>
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo strtoupper($user['role']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>