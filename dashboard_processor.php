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

// 4. HANDLE SYSTEM SETTINGS - MATCH PHASES (Admin Only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['setting_action'])) {
    if ($_SESSION['role'] == 'admin') {
        
        // --- ADD NEW MATCH PHASE ---
        if ($_POST['setting_action'] == 'add_phase') {
            $phase_name = trim($_POST['phase_name']);
            
            if (!empty($phase_name)) {
                // 1. Calculate the next available phase_order automatically
                $order_query = mysqli_query($conn, "SELECT COALESCE(MAX(phase_order), 0) AS max_order FROM match_phases");
                $order_row = mysqli_fetch_assoc($order_query);
                $next_order = $order_row['max_order'] + 1;

                // 2. Secure Prepared Statement inserting BOTH name and order
                $stmt = mysqli_prepare($conn, "INSERT INTO match_phases (phase_name, phase_order) VALUES (?, ?)");
                if ($stmt) {
                    // "si" means String (phase_name) and Integer (phase_order)
                    mysqli_stmt_bind_param($stmt, "si", $phase_name, $next_order);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['setting_msg'] = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>✅ Match phase '$phase_name' added successfully!</div>";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- DELETE MATCH PHASE ---
        if ($_POST['setting_action'] == 'delete_phase') {
            $id = (int)$_POST['phase_id'];
            
            // Secure Prepared Statement
            $stmt = mysqli_prepare($conn, "DELETE FROM match_phases WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['setting_msg'] = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>🗑️ Match phase deleted.</div>";
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: dashboard.php");
            exit();
        }
    }
}
// --- REORDER MATCH PHASE ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'move_phase') {
            $id = (int)$_POST['phase_id'];
            $direction = $_POST['direction']; // 'up' or 'down'

            // Get the current order of the phase we clicked
            $curr_query = mysqli_query($conn, "SELECT phase_order FROM match_phases WHERE id = $id");
            if ($row = mysqli_fetch_assoc($curr_query)) {
                $current_order = $row['phase_order'];

                // Find the neighbor to swap with
                if ($direction == 'up') {
                    // Find the phase right above it
                    $target_query = mysqli_query($conn, "SELECT id, phase_order FROM match_phases WHERE phase_order < $current_order ORDER BY phase_order DESC LIMIT 1");
                } else {
                    // Find the phase right below it
                    $target_query = mysqli_query($conn, "SELECT id, phase_order FROM match_phases WHERE phase_order > $current_order ORDER BY phase_order ASC LIMIT 1");
                }

                if ($target = mysqli_fetch_assoc($target_query)) {
                    $target_id = $target['id'];
                    $target_order = $target['phase_order'];

                    // Swap their phase_order numbers in the database
                    mysqli_query($conn, "UPDATE match_phases SET phase_order = $target_order WHERE id = $id");
                    mysqli_query($conn, "UPDATE match_phases SET phase_order = $current_order WHERE id = $target_id");
                }
            }
            header("Location: dashboard.php");
            exit();
        }

// --- ADD NEW VENUE ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'add_venue') {
            // We use strtoupper() to force the text to match your existing uppercase venue data
            $venue_name = strtoupper(trim($_POST['venue_name'])); 
            
            if (!empty($venue_name)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO venues_list (venue_name) VALUES (?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $venue_name);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['setting_msg'] = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>✅ Venue '$venue_name' added successfully!</div>";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- DELETE VENUE ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'delete_venue') {
            $id = (int)$_POST['venue_id'];
            
            $stmt = mysqli_prepare($conn, "DELETE FROM venues_list WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['setting_msg'] = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>🗑️ Venue deleted.</div>";
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- ADD NEW SPORT ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'add_sport') {
            $sport_name = trim($_POST['sport_name']);
            $format_type = $_POST['format_type']; // Will be 'h2h' or 'group'
            
            if (!empty($sport_name) && !empty($format_type)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO sports_list (sport_name, format_type) VALUES (?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $sport_name, $format_type);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['setting_msg'] = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>✅ Sport '$sport_name' added successfully!</div>";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- DELETE SPORT ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'delete_sport') {
            $id = (int)$_POST['sport_id'];
            
            $stmt = mysqli_prepare($conn, "DELETE FROM sports_list WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['setting_msg'] = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>🗑️ Sport deleted.</div>";
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- ADD NEW DISCIPLINE ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'add_discipline') {
            $discipline_name = trim($_POST['discipline_name']);
            if (!empty($discipline_name)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO disciplines_list (discipline_name) VALUES (?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $discipline_name);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['setting_msg'] = "<div style='color: white; background: #28a745; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>✅ Discipline '$discipline_name' added successfully!</div>";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            header("Location: dashboard.php");
            exit();
        }

        // --- DELETE DISCIPLINE ---
        if (isset($_POST['setting_action']) && $_POST['setting_action'] == 'delete_discipline') {
            $id = (int)$_POST['discipline_id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM disciplines_list WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['setting_msg'] = "<div style='color: white; background: #dc3545; padding: 10px; border-radius: 4px; margin-bottom: 15px;'>🗑️ Discipline deleted.</div>";
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: dashboard.php");
            exit();
        }
?>