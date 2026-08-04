<?php
require_once 'auth_guard.php';
require_once 'dashboard_processor.php';

// Fetch Data Queries
$events_result = mysqli_query($conn, "SELECT * FROM sports_events ORDER BY CASE WHEN match_status = 'Completed' THEN 2 ELSE 1 END, event_date ASC");
$news_result = mysqli_query($conn, "SELECT id, title, content, author, created_at FROM news ORDER BY created_at DESC");
$users_result = mysqli_query($conn, "SELECT id, username, email, role, created_at FROM users ORDER BY role ASC, created_at DESC");
$sports_query = mysqli_query($conn, "SELECT sport_name, format_type FROM sports_list ORDER BY sport_name ASC");
$venues_query = mysqli_query($conn, "SELECT venue_name FROM venues_list ORDER BY venue_name ASC");
$phases_query = mysqli_query($conn, "SELECT phase_name FROM match_phases ORDER BY phase_order ASC");
$athletes_query = mysqli_query($conn, "SELECT * FROM athletes ORDER BY contingent_state ASC, gender ASC, full_name ASC");    

$all_states = [
    'JOHOR' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'KEDAH' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'KELANTAN' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'MELAKA' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'NEGERI SEMBILAN' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'PAHANG' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'PERAK' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'PERLIS' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'PULAU PINANG' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'SABAH' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'SARAWAK' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'SELANGOR' => ['gold' => 0, 'silver' => 0, 'bronze' => 0],
    'TERENGGANU' => ['gold' => 0, 'silver' => 0, 'bronze' => 0]
];


$tally_query = "
    SELECT UPPER(state_name) as state_name, SUM(gold) as gold_count, SUM(silver) as silver_count, SUM(bronze) as bronze_count
    FROM (
        SELECT gold_winner as state_name, 1 as gold, 0 as silver, 0 as bronze 
        FROM sports_events 
        WHERE match_status = 'Completed' AND gold_winner IS NOT NULL AND gold_winner != ''
        UNION ALL

        SELECT silver_winner as state_name, 0 as gold, 1 as silver, 0 as bronze 
        FROM sports_events 
        WHERE match_status = 'Completed' AND silver_winner IS NOT NULL AND silver_winner != ''
        UNION ALL

        SELECT bronze_winner as state_name, 0 as gold, 0 as silver, 1 as bronze 
        FROM sports_events 
        WHERE match_status = 'Completed' AND bronze_winner IS NOT NULL AND bronze_winner != ''
    ) as medal_data
    GROUP BY state_name
";
$tally_result = mysqli_query($conn, $tally_query);

// 3. Inject the real medal counts into our Master Array
if ($tally_result) {
    while($row = mysqli_fetch_assoc($tally_result)) {
        $s_name = $row['state_name'];
        if(isset($all_states[$s_name])) {
            $all_states[$s_name]['gold'] = (int)$row['gold_count'];
            $all_states[$s_name]['silver'] = (int)$row['silver_count'];
            $all_states[$s_name]['bronze'] = (int)$row['bronze_count'];
        }
    }
}

// 4. Strict Olympic Sorting (Gold > Silver > Bronze > Alphabetical)
uksort($all_states, function($a, $b) use ($all_states) {
    if ($all_states[$a]['gold'] !== $all_states[$b]['gold']) return $all_states[$b]['gold'] <=> $all_states[$a]['gold'];
    if ($all_states[$a]['silver'] !== $all_states[$b]['silver']) return $all_states[$b]['silver'] <=> $all_states[$a]['silver'];
    if ($all_states[$a]['bronze'] !== $all_states[$b]['bronze']) return $all_states[$b]['bronze'] <=> $all_states[$a]['bronze'];
    return strcmp($a, $b); 
});



// 3. Fetch Latest News
// Fetch News: Admin sees all, everyone else sees only 'visible'
if ($_SESSION['role'] == 'admin') {
    $news_query = "SELECT * FROM news ORDER BY created_at DESC";
} else {
    $news_query = "SELECT * FROM news WHERE status = 'visible' ORDER BY created_at DESC";
}
$news_result = mysqli_query($conn, $news_query);

// Fetch Analytics Data
// Fetch Analytics Data (Using our dynamic Master Array!)
$states = []; $golds = []; $silvers = []; $bronzes = [];
// Slice only the top 5 contingents from our already-sorted array
$top_5_states = array_slice($all_states, 0, 5, true);

foreach($top_5_states as $state => $data) {
    $states[] = $state;
    $golds[] = $data['gold'];
    $silvers[] = $data['silver'];
    $bronzes[] = $data['bronze'];
}

// --- NEW: UI POLISH METRICS & TICKER DATA ---
// 1. Total Athletes
$total_athletes_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM athletes");
$total_athletes = $total_athletes_query ? mysqli_fetch_assoc($total_athletes_query)['count'] : 0;

// 2. Upcoming Matches
$upcoming_matches_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM sports_events WHERE match_status != 'Completed'");
$upcoming_matches = $upcoming_matches_query ? mysqli_fetch_assoc($upcoming_matches_query)['count'] : 0;

// 3. Total Gold Medals Awarded
$total_golds_awarded = 0;
foreach($all_states as $state_data) {
    $total_golds_awarded += $state_data['gold'];
}

// 4. Live Ticker Data
$latest_news = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM news ORDER BY created_at DESC LIMIT 1"));
$latest_match = mysqli_fetch_assoc(mysqli_query($conn, "SELECT event_name, gold_winner FROM sports_events WHERE match_status = 'Completed' AND gold_winner != '' ORDER BY id DESC LIMIT 1"));

$ticker_text = "🟢 SYSTEM LIVE ACTIVE  |  ";
if ($latest_news) $ticker_text .= "📰 LATEST NEWS: " . strtoupper(htmlspecialchars($latest_news['title'])) . "  |  ";
if ($latest_match) $ticker_text .= "🏆 BREAKING RESULT: " . strtoupper(htmlspecialchars($latest_match['gold_winner'])) . " secures Gold in " . strtoupper(htmlspecialchars($latest_match['event_name'])) . "!  |  ";

// 5. Activity Stream (Last 4 Completed Matches)
$recent_activities = mysqli_query($conn, "SELECT event_name, match_phase, gold_winner FROM sports_events WHERE match_status = 'Completed' ORDER BY id DESC LIMIT 4");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIS - Main Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="top-header">
    <div style="display: flex; align-items: center; gap: 15px;">
        <img src="assets/sukma_logo.png" alt="Logo" style="height: 100px; witdh: 100px;" onerror="this.style.display='none'">
        <h2 style="font-size:30px";>SUKMA Information System</h2>
    </div>
    <div class="user-controls">
        <span style="font-size:20px">Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
        <a href="#" class="btn-help">Help</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<style>
    .ticker-wrap { width: 100%; background: #c5413a; color: #ffffff; padding: 10px 0; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-bottom: 2px solid #b01c15; }
    .ticker { display: inline-block; white-space: nowrap; padding-right: 100%; animation: ticker 30s linear infinite; font-weight: 600; font-size: 13px; letter-spacing: 0.5px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .ticker-highlight { color: #ffdf00; font-weight: 900; text-shadow: 1px 1px 2px rgba(0,0,0,0.15); }
    
    /* This is the engine that makes it scroll! */
    @keyframes ticker { 
        0% { transform: translate3d(100%, 0, 0); } 
        100% { transform: translate3d(-100%, 0, 0); } 
    }
</style>
<?php
// Formatting the ticker text beautifully for a red background
$ticker_text = "<span class='ticker-highlight'>⚡ LIVE UPDATE</span> &nbsp;&nbsp;|&nbsp;&nbsp; ";
if ($latest_news) $ticker_text .= "📰 <span style='color: #ffcccc;'>LATEST NEWS:</span> " . strtoupper(htmlspecialchars($latest_news['title'])) . " &nbsp;&nbsp;|&nbsp;&nbsp; ";
if ($latest_match) $ticker_text .= "🏆 <span style='color: #ffcccc;'>BREAKING:</span> <span class='ticker-highlight'>" . strtoupper(htmlspecialchars($latest_match['gold_winner'])) . "</span> secures Gold in " . strtoupper(htmlspecialchars($latest_match['event_name'])) . "! &nbsp;&nbsp;|&nbsp;&nbsp; ";
?>

<div class="ticker-wrap">
    <div class="ticker"><?php echo $ticker_text; ?></div>
</div>

<div class="tab-container">
    <button class="chrome-tab" onclick="openTab(event, 'Dashboard')" id="defaultOpen">Dashboard</button>
    <button class="chrome-tab" onclick="openTab(event, 'Events')">Events Schedule</button>
    <button class="chrome-tab" onclick="openTab(event, 'News')">News</button>
    
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
        <button class="chrome-tab" onclick="openTab(event, 'Athletes')">Manage Athletes</button>
    <?php endif; ?>

    <?php if($_SESSION['role'] == 'admin'): ?>
        <button class="chrome-tab" onclick="openTab(event, 'ManageUsers')">Manage Users</button>
    <?php endif; ?>
</div>

<?php include 'tab_overview.php'; ?>
<?php include 'tab_news.php'; ?>
<?php include 'tab_events.php'; ?>
<?php include 'tab_athletes.php'; ?>
<?php include 'tab_manage_users.php'; ?>
<?php include 'dashboard_modals.php'; ?>
<?php include 'dashboard_scripts.php'; ?>


    <!-- SYSTEM ACCESSIBILITY ANCHOR FOOTER -->
    <div style="background: #1a1e23; color: #8a94a6; text-align: center; padding: 25px 20px; margin-top: 60px; border-top: 4px solid #da251d; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
        <div style="font-weight: 700; color: #f8f9fa; font-size: 14px; letter-spacing: 0.5px; margin-bottom: 4px;">
            SUKMA INFORMATION SYSTEM (SIS)
        </div>
        <div style="font-size: 11px; color: #64748b; font-weight: 500;">
            System Core Node v1.0.4 &bull; Secure Management Framework &bull; Developed by Ace
        </div>
    </div>


</body>
</html>