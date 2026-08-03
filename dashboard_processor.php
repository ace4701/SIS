<?php
// dashboard_processor.php - Handles all POST requests for the main dashboard

$user_msg = "";
$athlete_msg = "";

// 1. CATCH THE MESSAGE: If a message survived the redirect, grab it and clear it.
if (isset($_SESSION['athlete_msg'])) {
    $athlete_msg = $_SESSION['athlete_msg'];
    unset($_SESSION['athlete_msg']); 
}

// 2. HANDLE ADD USER (Admin Only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    if ($_SESSION['role'] == 'admin') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $user_msg = "<div style='color:red;'>Username or Email already exists.</div>";
        } else {
            mysqli_query($conn, "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')");
            $user_msg = "<div style='color:green;'>User $username successfully added.</div>";
        }
    }
}

// 3. HANDLE ATHLETE MANAGEMENT (Admin & Staff)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff') {
        
        if ($_POST['action'] == 'add_athlete') {
            $name = mysqli_real_escape_string($conn, strtoupper($_POST['full_name']));
            $state = mysqli_real_escape_string($conn, $_POST['contingent_state']);
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);

            if (mysqli_query($conn, "INSERT INTO athletes (full_name, contingent_state, gender) VALUES ('$name', '$state', '$gender')")) {
                $_SESSION['athlete_msg'] = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>✅ Athlete '$name' registered!</div>";
                header("Location: dashboard.php");
                exit();
            }
        
        } elseif ($_POST['action'] == 'edit_athlete') {
            $id = (int)$_POST['athlete_id'];
            $name = mysqli_real_escape_string($conn, strtoupper($_POST['full_name']));
            $state = mysqli_real_escape_string($conn, $_POST['contingent_state']);
            $gender = mysqli_real_escape_string($conn, $_POST['gender']);

            if (mysqli_query($conn, "UPDATE athletes SET full_name='$name', contingent_state='$state', gender='$gender' WHERE id=$id")) {
                $_SESSION['athlete_msg'] = "<div style='color: white; background: #0056b3; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>✏️ Athlete '$name' updated!</div>";
                header("Location: dashboard.php");
                exit();
            }

        } elseif ($_POST['action'] == 'delete_athlete') {
            $id = (int)$_POST['athlete_id'];
            if (mysqli_query($conn, "DELETE FROM athletes WHERE id = $id")) {
                $_SESSION['athlete_msg'] = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>🗑️ Athlete removed.</div>";
                header("Location: dashboard.php");
                exit();
            }

        } elseif ($_POST['action'] == 'import_csv') {
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
                $file = $_FILES['csv_file']['tmp_name'];
                $handle = fopen($file, "r");
                $count = 0;
                
                fgetcsv($handle); // Skip header
                
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 3) {
                        $name = mysqli_real_escape_string($conn, strtoupper(trim($data[0])));
                        $state = mysqli_real_escape_string($conn, trim($data[1]));
                        $gender = mysqli_real_escape_string($conn, trim($data[2]));
                        
                        if (!empty($name) && !empty($state)) {
                            mysqli_query($conn, "INSERT INTO athletes (full_name, contingent_state, gender) VALUES ('$name', '$state', '$gender')");
                            $count++;
                        }
                    }
                }
                fclose($handle);
                $_SESSION['athlete_msg'] = "<div style='color: white; background: #17a2b8; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold;'>📁 Successfully imported $count athletes!</div>";
                header("Location: dashboard.php");
                exit();
            }
        }
    }
}
?>