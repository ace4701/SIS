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
    $event_discipline = mysqli_real_escape_string($conn, $data['event_discipline']); // <-- NEW
    $venue = mysqli_real_escape_string($conn, $data['venue']);
    $event_date = mysqli_real_escape_string($conn, $data['event_date']);
    $event_time = mysqli_real_escape_string($conn, $data['event_time']); // <-- NEW
    $match_phase = mysqli_real_escape_string($conn, $data['match_phase']); 
    
    $states_json = json_encode($data['states']);
    $states_escaped = mysqli_real_escape_string($conn, $states_json);

    // Added discipline and time to the INSERT query
    $insert_query = "INSERT INTO sports_events (event_name, event_discipline, venue, event_date, event_time, match_phase, participating_states) 
                     VALUES ('$event_name', '$event_discipline', '$venue', '$event_date', '$event_time', '$match_phase', '$states_escaped')";
    
    if (mysqli_query($conn, $insert_query)) {
        echo json_encode(['status' => 'success']);
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
            'event_discipline' => $event['event_discipline'] ?? '',
            'match_phase' => $event['match_phase'] ?? '',
            'venue' => $event['venue'] ?? '',
            'event_date' => $event['event_date'] ?? '',
            'event_time' => $event['event_time'] ?? '',
            'format_type' => $event['format_type'] ?? '',
            'states' => json_decode($states_raw, true),
            
            // The medals (silenced in case they are completely missing)
            'gold_winner' => $event['gold_winner'] ?? null,
            'silver_winner' => $event['silver_winner'] ?? null,
            'bronze_winner' => $event['bronze_winner'] ?? null,
            'match_winner' => $event['match_winner'] ?? null
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
    $event_discipline = mysqli_real_escape_string($conn, $data['event_discipline']); // <-- NEW
    $venue = mysqli_real_escape_string($conn, $data['venue']);
    $event_date = mysqli_real_escape_string($conn, $data['event_date']);
    $event_time = mysqli_real_escape_string($conn, $data['event_time']); // <-- NEW
    $match_phase = mysqli_real_escape_string($conn, $data['match_phase']); 
    $states_json = mysqli_real_escape_string($conn, json_encode($data['states']));

    // Added discipline and time to the UPDATE query
    $update_query = "UPDATE sports_events SET event_name='$event_name', event_discipline='$event_discipline', venue='$venue', event_date='$event_date', event_time='$event_time', match_phase='$match_phase', participating_states='$states_json' WHERE id=$event_id";
    
    if (mysqli_query($conn, $update_query)) {
        echo json_encode(['status' => 'success']);
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
    $match_winner = mysqli_real_escape_string($conn, $data['match_winner']); // <-- NEW
    
    // Update winners and auto-complete the match!
    $query = "UPDATE sports_events SET 
              match_status = 'Completed', 
              gold_winner = IF('$gold'='', NULL, '$gold'), 
              silver_winner = IF('$silver'='', NULL, '$silver'), 
              bronze_winner = IF('$bronze'='', NULL, '$bronze'),
              match_winner = IF('$match_winner'='', NULL, '$match_winner') 
              WHERE id = $event_id";
              
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

// --- 6. FETCH AVAILABLE EVENTS FOR ATHLETE ASSIGNMENT ---
if (isset($data['action']) && $data['action'] == 'fetch_state_events') {
    $state = mysqli_real_escape_string($conn, $data['state']);
    
    // Find upcoming matches where this athlete's state is participating
    $query = "SELECT id, event_name, match_phase FROM sports_events WHERE match_status != 'Completed' AND participating_states LIKE '%\"$state\"%'";
    $result = mysqli_query($conn, $query);
    
    $events = [];
    while($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    echo json_encode(['status' => 'success', 'events' => $events]);
    exit();
}

// --- 7. ASSIGN ATHLETE TO EVENT ---
if (isset($data['action']) && $data['action'] == 'assign_athlete') {
    $athlete_id = (int)$data['athlete_id'];
    $event_id = (int)$data['event_id'];
    
    // Check if already assigned to prevent duplicates
    $check = mysqli_query($conn, "SELECT id FROM athlete_event_links WHERE athlete_id = $athlete_id AND event_id = $event_id");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Athlete is already assigned to this match!']);
    } else {
        if(mysqli_query($conn, "INSERT INTO athlete_event_links (athlete_id, event_id) VALUES ($athlete_id, $event_id)")) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    }
    exit();
}

// --- 8. FETCH ATHLETE PROFILE & ASSIGNED EVENTS ---
if (isset($data['action']) && $data['action'] == 'fetch_athlete_profile') {
    $athlete_id = (int)$data['athlete_id'];
    
    // 1. Get basic athlete info
    $athlete_query = mysqli_query($conn, "SELECT * FROM athletes WHERE id = $athlete_id");
    $athlete = mysqli_fetch_assoc($athlete_query);
    
    if($athlete) {
        // 2. Magic SQL JOIN: Get all events this specific athlete is linked to!
        $events_query = mysqli_query($conn, "
            SELECT e.event_name, e.match_phase, e.match_status, e.event_date 
            FROM athlete_event_links ael
            JOIN sports_events e ON ael.event_id = e.id
            WHERE ael.athlete_id = $athlete_id
            ORDER BY e.event_date ASC
        ");
        
        $assigned_events = [];
        while($row = mysqli_fetch_assoc($events_query)) {
            $assigned_events[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'athlete' => $athlete, 'events' => $assigned_events]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Athlete not found.']);
    }
    exit();
}

// --- 9. FETCH EVENT PARTICIPANTS ---
if (isset($data['action']) && $data['action'] == 'fetch_event_participants') {
    $event_id = (int)$data['event_id'];
    
    // Join the bridge table with the athletes table
    $query = mysqli_query($conn, "
        SELECT a.id, a.full_name, a.contingent_state, a.gender 
        FROM athlete_event_links ael
        JOIN athletes a ON ael.athlete_id = a.id
        WHERE ael.event_id = $event_id
        ORDER BY a.contingent_state ASC, a.full_name ASC
    ");
    
    $participants = [];
    while($row = mysqli_fetch_assoc($query)) {
        $participants[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'participants' => $participants]);
    exit();
}

// --- 10. FETCH MEDAL WINNERS (DEEP LINKING) ---
if (isset($data['action']) && $data['action'] == 'fetch_medal_winners') {
    $state = mysqli_real_escape_string($conn, $data['state']);
    $medal_type = mysqli_real_escape_string($conn, $data['medal_type']); // 'gold', 'silver', 'bronze', or 'total'

    // Figure out which columns to check based on what they clicked
    $where_clause = "";
    if ($medal_type == 'gold') {
        $where_clause = "e.gold_winner = '$state'";
    } elseif ($medal_type == 'silver') {
        $where_clause = "e.silver_winner = '$state'";
    } elseif ($medal_type == 'bronze') {
        $where_clause = "e.bronze_winner = '$state'";
    } else { 
        $where_clause = "(e.gold_winner = '$state' OR e.silver_winner = '$state' OR e.bronze_winner = '$state')";
    }

    // Magic SQL: Find completed events where this state won, THEN find their athletes assigned to it!
    $query = mysqli_query($conn, "
        SELECT a.id, a.full_name, e.event_name, 
               CASE 
                   WHEN e.gold_winner = '$state' THEN 'Gold'
                   WHEN e.silver_winner = '$state' THEN 'Silver'
                   WHEN e.bronze_winner = '$state' THEN 'Bronze'
               END as medal_won
        FROM sports_events e
        JOIN athlete_event_links ael ON e.id = ael.event_id
        JOIN athletes a ON ael.athlete_id = a.id
        WHERE e.match_status = 'Completed' AND a.contingent_state = '$state' AND $where_clause
        ORDER BY medal_won ASC, e.event_name ASC, a.full_name ASC
    ");
    
    $winners = [];
    while($row = mysqli_fetch_assoc($query)) {
        $winners[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'winners' => $winners]);
    exit();
}

// --- 11. REMOVE ATHLETE FROM EVENT (UNASSIGN) ---
if (isset($data['action']) && $data['action'] == 'remove_athlete') {
    $athlete_id = (int)$data['athlete_id'];
    $event_id = (int)$data['event_id'];
    
    // Delete the specific bridge connection
    if(mysqli_query($conn, "DELETE FROM athlete_event_links WHERE athlete_id = $athlete_id AND event_id = $event_id")) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    exit();
}

// --- 12. FETCH SPORT-SPECIFIC DOMINANCE ---
if (isset($data['action']) && $data['action'] == 'fetch_sport_dominance') {
    $sport = mysqli_real_escape_string($conn, $data['sport']);
    
    // Magic SQL: Calculate medals only for the requested sport and grab the Top 5 states!
    $query = mysqli_query($conn, "
        SELECT UPPER(state_name) as state_name, SUM(gold) as gold_count, SUM(silver) as silver_count, SUM(bronze) as bronze_count
        FROM (
            SELECT gold_winner as state_name, 1 as gold, 0 as silver, 0 as bronze 
            FROM sports_events 
            WHERE match_status = 'Completed' AND gold_winner != '' AND event_name = '$sport'
            
            UNION ALL
            
            SELECT silver_winner as state_name, 0 as gold, 1 as silver, 0 as bronze 
            FROM sports_events 
            WHERE match_status = 'Completed' AND silver_winner != '' AND event_name = '$sport'
            
            UNION ALL
            
            SELECT bronze_winner as state_name, 0 as gold, 0 as silver, 1 as bronze 
            FROM sports_events 
            WHERE match_status = 'Completed' AND bronze_winner != '' AND event_name = '$sport'
        ) as medal_data
        GROUP BY state_name
        ORDER BY gold_count DESC, silver_count DESC, bronze_count DESC
        LIMIT 5
    ");
    
    $standings = [];
    if ($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $standings[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'standings' => $standings]);
    exit();
}

// --- 13. FETCH ATHLETE-TO-MEDAL EFFICIENCY ---
if (isset($data['action']) && $data['action'] == 'fetch_efficiency') {
    // 1. Count athletes per state
    $athlete_query = mysqli_query($conn, "SELECT UPPER(contingent_state) as state, COUNT(id) as a_count FROM athletes GROUP BY contingent_state");
    $athletes = [];
    while($row = mysqli_fetch_assoc($athlete_query)) {
        $athletes[$row['state']] = (int)$row['a_count'];
    }

    // 2. Count total medals per state
    $medal_query = mysqli_query($conn, "
        SELECT UPPER(state_name) as state, SUM(total) as m_count
        FROM (
            SELECT gold_winner as state_name, 1 as total FROM sports_events WHERE match_status = 'Completed' AND gold_winner != ''
            UNION ALL
            SELECT silver_winner as state_name, 1 as total FROM sports_events WHERE match_status = 'Completed' AND silver_winner != ''
            UNION ALL
            SELECT bronze_winner as state_name, 1 as total FROM sports_events WHERE match_status = 'Completed' AND bronze_winner != ''
        ) as all_medals
        GROUP BY state
    ");
    $medals = [];
    while($row = mysqli_fetch_assoc($medal_query)) {
        $medals[$row['state']] = (int)$row['m_count'];
    }

    // 3. Calculate Efficiency Ratio
    $efficiency_data = [];
    foreach($athletes as $state => $a_count) {
        $m_count = isset($medals[$state]) ? $medals[$state] : 0;
        // Efficiency = (Medals / Athletes) * 100
        $ratio = ($a_count > 0) ? round(($m_count / $a_count) * 100, 1) : 0; 
        
        $efficiency_data[] = [
            'state' => $state,
            'athletes' => $a_count,
            'medals' => $m_count,
            'ratio' => $ratio
        ];
    }

    // Sort by highest efficiency first!
    usort($efficiency_data, function($a, $b) {
        return $b['ratio'] <=> $a['ratio'];
    });

    echo json_encode(['status' => 'success', 'data' => $efficiency_data]);
    exit();
}



?>