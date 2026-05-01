<?php
session_start();
require 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// --- 1. ADD EVENT LOGIC ---
if (isset($data['action']) && $data['action'] == 'add_event') {
    $event_name = mysqli_real_escape_string($conn, $data['event_name']);
    $venue = mysqli_real_escape_string($conn, $data['venue']);
    $event_date = mysqli_real_escape_string($conn, $data['event_date']);
    $match_phase = mysqli_real_escape_string($conn, $data['match_phase']); // <-- NEW
    
    $states_json = json_encode($data['states']);
    $states_escaped = mysqli_real_escape_string($conn, $states_json);

    // Added match_phase to the INSERT query
    $insert_query = "INSERT INTO sports_events (event_name, venue, event_date, match_phase, participating_states) 
                     VALUES ('$event_name', '$venue', '$event_date', '$match_phase', '$states_escaped')";
    
    if (mysqli_query($conn, $insert_query)) {
        $formatted_date = date('d M Y', strtotime($event_date));
        
        $participants_html = "TBD";
        if (!empty($data['states']) && is_array($data['states'])) {
            if (count($data['states']) == 2 && $data['format_type'] == 'h2h') {
                $participants_html = "<span style='color:#da251d; font-weight:bold;'>" . htmlspecialchars($data['states'][0]) . "</span> <span style='font-size:11px; color:#777; margin:0 5px;'>vs</span> <span style='color:#0056b3; font-weight:bold;'>" . htmlspecialchars($data['states'][1]) . "</span>";
            } else {
                $participants_html = htmlspecialchars(implode(', ', $data['states']));
            }
        }
        
        echo json_encode([
            'status' => 'success', 
            // Combine Sport and Phase for a beautiful display!
            'event_name' => htmlspecialchars($event_name) . " <br><span style='font-size:12px; color:#666;'>➔ " . htmlspecialchars($match_phase) . "</span>", 
            'venue' => htmlspecialchars($venue), 
            'date' => $formatted_date,
            'participants' => $participants_html
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit();
}

// --- 2. DELETE EVENT LOGIC ---
if (isset($data['action']) && $data['action'] == 'delete_event') {
    $event_id = (int)$data['event_id'];
    if (mysqli_query($conn, "DELETE FROM sports_events WHERE id = $event_id")) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit();
}

if ($data['action'] == 'fetch_event') {
    $event_id = (int)$data['event_id'];
    
    $query = "SELECT * FROM sports_events WHERE id = $event_id";
    $result = mysqli_query($conn, $query);
    
    if ($event = mysqli_fetch_assoc($result)) {
        
        // Anti-Crash Silencer for older matches with missing states
        $states_raw = $event['participating_states'] ?? '[]';
        if (empty($states_raw)) { $states_raw = '[]'; } 

        // Carefully package the JSON response with silencers on every variable
        echo json_encode([
            'status' => 'success',
            'id' => $event['id'],
            'event_name' => $event['event_name'] ?? '',
            'match_phase' => $event['match_phase'] ?? '',
            'venue' => $event['venue'] ?? '',
            'event_date' => $event['event_date'] ?? '',
            'format_type' => $event['format_type'] ?? '',
            'states' => json_decode($states_raw, true),
            
            // The medals (silenced in case they are completely missing)
            'gold_winner' => $event['gold_winner'] ?? null,
            'silver_winner' => $event['silver_winner'] ?? null,
            'bronze_winner' => $event['bronze_winner'] ?? null
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    }
    exit;
}

// --- 4. EDIT EVENT LOGIC ---
if (isset($data['action']) && $data['action'] == 'edit_event') {
    $event_id = (int)$data['event_id'];
    $event_name = mysqli_real_escape_string($conn, $data['event_name']);
    $venue = mysqli_real_escape_string($conn, $data['venue']);
    $event_date = mysqli_real_escape_string($conn, $data['event_date']);
    $match_phase = mysqli_real_escape_string($conn, $data['match_phase']); // <-- NEW
    $states_json = mysqli_real_escape_string($conn, json_encode($data['states']));

    // Added match_phase to UPDATE query
    $update_query = "UPDATE sports_events SET event_name='$event_name', venue='$venue', event_date='$event_date', match_phase='$match_phase', participating_states='$states_json' WHERE id=$event_id";
    
    if (mysqli_query($conn, $update_query)) {
        $formatted_date = date('d M Y', strtotime($event_date));
        $participants_html = "TBD";
        if (!empty($data['states']) && is_array($data['states'])) {
            if (count($data['states']) == 2 && $data['format_type'] == 'h2h') {
                $participants_html = "<span style='color:#da251d; font-weight:bold;'>" . htmlspecialchars($data['states'][0]) . "</span> <span style='font-size:11px; color:#777; margin:0 5px;'>vs</span> <span style='color:#0056b3; font-weight:bold;'>" . htmlspecialchars($data['states'][1]) . "</span>";
            } else {
                $participants_html = htmlspecialchars(implode(', ', $data['states']));
            }
        }
        
        echo json_encode([
            'status' => 'success', 
            // Combine Sport and Phase for a beautiful display!
            'event_name' => htmlspecialchars($event_name) . " <br><span style='font-size:12px; color:#666;'>➔ " . htmlspecialchars($match_phase) . "</span>", 
            'venue' => htmlspecialchars($venue), 
            'date' => $formatted_date,
            'participants' => $participants_html
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit();
}

// --- 5. SET RESULT LOGIC ---
if (isset($data['action']) && $data['action'] == 'set_result') {
    $event_id = (int)$data['event_id'];
    $gold = mysqli_real_escape_string($conn, $data['gold']);
    $silver = mysqli_real_escape_string($conn, $data['silver']);
    $bronze = mysqli_real_escape_string($conn, $data['bronze']);
    
    // We update the winners AND automatically change the status to Completed!
    $query = "UPDATE sports_events SET 
              match_status = 'Completed', 
              gold_winner = IF('$gold'='', NULL, '$gold'), 
              silver_winner = IF('$silver'='', NULL, '$silver'), 
              bronze_winner = IF('$bronze'='', NULL, '$bronze') 
              WHERE id = $event_id";
              
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

?>