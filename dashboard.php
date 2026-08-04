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
    <button class="chrome-tab" onclick="openTab(event, 'News')">News</button>
    <button class="chrome-tab" onclick="openTab(event, 'Events')">Events Schedule</button>
    
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
        <button class="chrome-tab" onclick="openTab(event, 'Athletes')">Manage Athletes</button>
    <?php endif; ?>

    <?php if($_SESSION['role'] == 'admin'): ?>
        <button class="chrome-tab" onclick="openTab(event, 'ManageUsers')">Manage Users</button>
    <?php endif; ?>
</div>

<div id="Dashboard" class="tab-content">

    <div style="display: flex; gap: 20px; margin: 20px;">
        
        <div style="flex: 1; background: white; border-radius: 8px; border-left: 5px solid #0056b3; padding: 20px 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px;">Total Athletes</div>
                <div style="font-size: 32px; font-weight: 800; color: #222; margin-top: 5px;"><?php echo $total_athletes; ?></div>
            </div>
            <div style="font-size: 35px; opacity: 0.8;">🪪</div>
        </div>
        
        <div style="flex: 1; background: white; border-radius: 8px; border-left: 5px solid #28a745; padding: 20px 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px;">Upcoming Matches</div>
                <div style="font-size: 32px; font-weight: 800; color: #222; margin-top: 5px;"><?php echo $upcoming_matches; ?></div>
            </div>
            <div style="font-size: 35px; opacity: 0.8;">⏳</div>
        </div>
        
        <div style="flex: 1; background: white; border-radius: 8px; border-left: 5px solid #d4af37; padding: 20px 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px;">Golds Awarded</div>
                <div style="font-size: 32px; font-weight: 800; color: #222; margin-top: 5px;"><?php echo $total_golds_awarded; ?> <span style="font-size: 14px; color: #aaa; font-weight: 600;">medals</span></div>
            </div>
            <div style="font-size: 35px; opacity: 0.8;">🏆</div>
        </div>
        
    </div> 

    <div class="dashboard-wrapper">
        
        <div class="side-panel">
            <div class="info-box">
                <h4 class="info-header" style="background-color: #17a2b8;">Upcoming Events</h4>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php 
                    mysqli_data_seek($events_result, 0);
                    $count = 0;
                    while(($event = mysqli_fetch_assoc($events_result)) && $count < 5) { 
                        $count++;
                    ?>
                        <a href="#" onclick="openTab(event, 'Events')" class="feed-item small-feed">
                            <strong><?php echo $event['event_name']; ?></strong>
                            <div class="small-feed-date">&#128197; <?php echo date('d M', strtotime($event['event_date'])); ?></div>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="info-box" style="margin-top: 25px;">
                <h4 class="info-header" style="background-color: #e73434;">⚡ Live Activity Stream</h4>
                <div style="padding: 20px 15px; max-height: 280px; overflow-y: auto; background: #fff;">
                    
                    <?php if($recent_activities && mysqli_num_rows($recent_activities) > 0): ?>
                        <?php while($activity = mysqli_fetch_assoc($recent_activities)): ?>
                            <div style="border-left: 3px solid #0056b3; padding-left: 15px; margin-bottom: 20px; position: relative;">
                                <div style="position: absolute; left: -7px; top: 0; width: 11px; height: 11px; background: #0056b3; border-radius: 50%; border: 2px solid white;"></div>
                                <div style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase;">Recent Action</div>
                                <div style="font-size: 13px; color: #333; margin-top: 4px; line-height: 1.4;">
                                    Result confirmed for <strong><?php echo $activity['event_name']; ?></strong> (<?php echo $activity['match_phase']; ?>).
                                    <?php if($activity['gold_winner']): ?>
                                        <br><span style="color: #da251d; font-weight: bold;">🏅 <?php echo strtoupper($activity['gold_winner']); ?></span> took the victory!
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    
                    <div style="border-left: 3px solid #28a745; padding-left: 15px; position: relative;">
                        <div style="position: absolute; left: -7px; top: 0; width: 11px; height: 11px; background: #28a745; border-radius: 50%; border: 2px solid white;"></div>
                        <div style="font-size: 11px; color: #888; font-weight: bold; text-transform: uppercase;">System Node</div>
                        <div style="font-size: 13px; color: #333; margin-top: 4px;">Database synchronized successfully. Listening for live updates...</div>
                    </div>

                </div>
            </div>

            <!-- VENUE WEATHER & CONDITIONS WIDGET -->
            <div class="info-box" style="margin-top: 25px;">
                <h4 class="info-header" style="background-color: #343a40; border-top: 3px solid #4facfe; color: white;">🌤️ Venue Conditions</h4>
                
                <!-- Main Weather Display -->
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; color: white; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Kuching, Sarawak</div>
                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 15px;">Main Stadium & Aquatics Center</div>

                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                        <div style="font-size: 48px; line-height: 1; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));">⛅</div>
                        <div style="text-align: left;">
                            <div style="font-size: 36px; font-weight: 800; line-height: 1;">31°C</div>
                            <div style="font-size: 13px; font-weight: 600; opacity: 0.9;">Partly Cloudy</div>
                        </div>
                    </div>

                    <!-- Sports Metrics Bottom Bar -->
                    <div style="display: flex; justify-content: space-between; margin-top: 20px; font-size: 12px; background: rgba(0,0,0,0.15); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);">
                        <div style="flex: 1; text-align: center; border-right: 1px solid rgba(255,255,255,0.2);">
                            <div style="opacity: 0.8; margin-bottom: 3px;">Humidity</div>
                            <div style="font-weight: bold; font-size: 14px;">78%</div>
                        </div>
                        <div style="flex: 1; text-align: center; border-right: 1px solid rgba(255,255,255,0.2);">
                            <div style="opacity: 0.8; margin-bottom: 3px;">Wind</div>
                            <div style="font-weight: bold; font-size: 14px;">12 km/h</div>
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <div style="opacity: 0.8; margin-bottom: 3px;">Visibility</div>
                            <div style="font-weight: bold; font-size: 14px;">Clear</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Status Banner -->
                <div style="padding: 10px; text-align: center; font-size: 11px; color: #155724; background-color: #d4edda; border-top: 1px solid #c3e6cb; font-weight: bold;">
                    🟢 All outdoor events proceeding as scheduled.
                </div>
            </div>
            
        </div>

        <div class="center-panel">

            <!-- UPGRADED INTEGRATED ANALYTICS CHART -->
            <div style="margin-top: 40px; background: white; padding: 25px 20px 10px 20px; border-radius: 8px; border-top: 4px solid #343a40; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <h3 id="analytics_chart_title" style="margin: 0; color: #333; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">📊 Top 5 Contingents</h3>
                    
                    <div style="display: flex; gap: 10px;">
                        <!-- THE NEW SPORT DOMINANCE FILTER -->
                        <select id="analytics_sport_filter" onchange="updateSportDominance()" style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 20px; font-size: 13px; font-weight: bold; color: #0056b3; outline: none; cursor: pointer; background: #e6f2ff;">
                            <option value="ALL">All Sports</option>
                            <?php 
                            mysqli_data_seek($sports_query, 0); 
                            while($sport = mysqli_fetch_assoc($sports_query)) {
                                echo "<option value='" . htmlspecialchars($sport['sport_name']) . "'>" . $sport['sport_name'] . "</option>"; 
                            }
                            ?>
                        </select>

                        <!-- THE EXISTING CONTINGENT DEEP DIVE FILTER -->
                        <select id="analytics_state_filter" onchange="updateAnalyticsChart()" style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 20px; font-size: 13px; font-weight: bold; color: #333; outline: none; cursor: pointer; background: #f8f9fa;">
                            <option value="ALL">Overview (Top 5 States)</option>
                            <?php foreach(array_keys($all_states) as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button onclick="viewEfficiency()" style="padding: 6px 15px; background: #28a745; color: white; border: none; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            ⚖️ Efficiency ROI
                        </button>
                </div>

                <div style="width: 100%; margin: 0 auto; position: relative; height: 280px; display: flex; justify-content: center;">
                    <canvas id="medalChart"></canvas>
                </div>
            </div>

            <h3 style="margin-top: 0; text-align: center;">Official Medal Tally</h3>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th style="text-align: left; padding-left: 20px;">State Contingent</th>
                        <th>Gold</th>
                        <th>Silver</th>
                        <th>Bronze</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    // We loop through our perfectly sorted Master Array ($all_states) instead of the raw SQL
                    foreach($all_states as $state_name => $medals) { 
                        $total = $medals['gold'] + $medals['silver'] + $medals['bronze'];
                        
                        // Your brilliant filename generator, adapted for the array variable
                        $image_filename = strtolower(str_replace(' ', '_', $state_name)) . '.png';
                    ?>
                    <tr>
                        <td class="center" style="font-weight:bold; color:#777;"><?php echo $rank++; ?></td>
                        
                        <td class="state-cell">
                            <div class="state-flag-bg" style="background-image: url('assets/flags/<?php echo $image_filename; ?>');"></div>
                            <div class="state-name-text"><?php echo strtoupper($state_name); ?></div>
                        </td>
                        
                        <td class="center col-gold">
                            <?php if($medals['gold'] > 0): ?>
                                <a href="#" onclick="openMedalWinnersModal('<?php echo addslashes($state_name); ?>', 'gold'); return false;" style="background: #fffdf5; border: 1px solid #ffecb3; color: #d4af37; padding: 4px 14px; border-radius: 20px; font-weight: bold; text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;" onmouseover="this.style.background='#d4af37'; this.style.color='#fff';" onmouseout="this.style.background='#fffdf5'; this.style.color='#d4af37';"><?php echo $medals['gold']; ?></a>
                            <?php else: echo "<span style='color: #ccc;'>0</span>"; endif; ?>
                        </td>

                        <td class="center col-silver">
                            <?php if($medals['silver'] > 0): ?>
                                <a href="#" onclick="openMedalWinnersModal('<?php echo addslashes($state_name); ?>', 'silver'); return false;" style="background: #f8f9fa; border: 1px solid #ddd; color: #777; padding: 4px 14px; border-radius: 20px; font-weight: bold; text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;" onmouseover="this.style.background='#777'; this.style.color='#fff';" onmouseout="this.style.background='#f8f9fa'; this.style.color='#777';"><?php echo $medals['silver']; ?></a>
                            <?php else: echo "<span style='color: #ccc;'>0</span>"; endif; ?>
                        </td>

                        <td class="center col-bronze">
                            <?php if($medals['bronze'] > 0): ?>
                                <a href="#" onclick="openMedalWinnersModal('<?php echo addslashes($state_name); ?>', 'bronze'); return false;" style="background: #fdf6f2; border: 1px solid #f5d0b5; color: #cd7f32; padding: 4px 14px; border-radius: 20px; font-weight: bold; text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;" onmouseover="this.style.background='#cd7f32'; this.style.color='#fff';" onmouseout="this.style.background='#fdf6f2'; this.style.color='#cd7f32';"><?php echo $medals['bronze']; ?></a>
                            <?php else: echo "<span style='color: #ccc;'>0</span>"; endif; ?>
                        </td>

                        <td class="center" style="font-size: 18px;">
                            <strong>
                                <?php if($total > 0): ?>
                                    <a href="#" onclick="openMedalWinnersModal('<?php echo addslashes($state_name); ?>', 'total'); return false;" style="background: #e6f2ff; border: 1px solid #b3d7ff; color: #0056b3; padding: 4px 16px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.2s;" onmouseover="this.style.background='#0056b3'; this.style.color='#fff';" onmouseout="this.style.background='#e6f2ff'; this.style.color='#0056b3';"><?php echo $total; ?></a>
                                <?php else: echo "<span style='color: #ccc;'>0</span>"; endif; ?>
                            </strong>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

        <div class="side-panel">
            <div class="info-box">
                <h4 class="info-header" style="background-color: #28a745;">Latest Announcements</h4>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php 
                    mysqli_data_seek($news_result, 0);
                    $count = 0;
                    while(($news = mysqli_fetch_assoc($news_result)) && $count < 5) { 
                        $count++;
                    ?>
                        <a href="#" onclick="openTab(event, 'News')" class="feed-item small-feed">
                            <strong><?php echo $news['title']; ?></strong>
                            <div class="small-feed-date">&#128337; <?php echo date('d M, H:i', strtotime($news['created_at'])); ?></div>
                        </a>
                    <?php } ?>
                </div>
            </div>
            
            <!-- SYSTEM NOTES & QUICK NAVIGATION GUIDE -->
            <div class="info-box" style="margin-top: 25px;">
                <h4 class="info-header" style="background-color: #343a40; border-top: 3px solid #0056b3; color: white;">💡 System Guide & Notes</h4>
                <div style="padding: 15px; background: #fff; font-size: 13px; line-height: 1.5; color: #444;">
                    
                    <!-- General Navigation Tips for Everyone -->
                    <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #eee;">
                        <span style="font-weight: bold; color: #da251d;">🔍 Interactive Data:</span> 
                        Click on any number greater than zero in the <strong>Medal Tally</strong> table to open up the deep-dive athlete roster for that specific state achievement.
                    </div>

                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <!-- Admin Specific Operational Guide Notes -->
                        <div style="background: #f1f7fc; border-left: 3px solid #0056b3; padding: 10px; border-radius: 0 4px 4px 0;">
                            <strong style="color: #0056b3; display: block; margin-bottom: 4px;">🛠️ Administrator Workflow:</strong>
                            <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #555;">
                                <li style="margin-bottom: 4px;">Use the <strong style="color: #333;">Manage Users</strong> tab to provision credentials for incoming field officials.</li>
                                <li style="margin-bottom: 4px;">To link an athlete to a live event roster, navigate to <strong style="color: #333;">Manage Athletes</strong> and click the 📅 icon.</li>
                                <li>The ⚙️ filter in <strong style="color: #333;">Events Schedule</strong> allows multi-tiered compound sorting across venues, phases, and dates.</li>
                            </ul>
                        </div>
                    <?php elseif($_SESSION['role'] == 'staff'): ?>
                        <!-- Staff Specific Operational Guide Notes -->
                        <div style="background: #f4faf6; border-left: 3px solid #28a745; padding: 10px; border-radius: 0 4px 4px 0;">
                            <strong style="color: #28a745; display: block; margin-bottom: 4px;">📋 Official Duties Checklist:</strong>
                            <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #555;">
                                <li style="margin-bottom: 4px;">Go to <strong style="color: #333;">Events Schedule</strong> and click the 🏆 trophy icon to submit validated medal match results.</li>
                                <li style="margin-bottom: 4px;">Utilize the bulk CSV tool to swiftly import entire contingent clusters simultaneously.</li>
                                <li>Toggle <strong style="color: #333;">Hide Completed</strong> to separate real-time ongoing brackets from finalized records.</li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 12px; font-size: 11px; text-align: center; color: #999; font-style: italic;">
                        Secured Session Node: <?php echo strtoupper($_SESSION['role']); ?> Mode
                    </div>
                </div>
            </div>

            <!-- HIGHLIGHTS CAROUSEL -->
            <div class="info-box" style="margin-top: 25px;">
                <h4 class="info-header" style="background-color: #343a40; border-top: 3px solid #ffdf00; color: white;">📸 Official Highlights</h4>
                <div style="position: relative; width: 100%; height: 200px; overflow: hidden; background: #eee;">
                    <style>
                        .fade-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; animation: fade 12s infinite; }
                        .fade-slide:nth-child(1) { animation-delay: 0s; }
                        .fade-slide:nth-child(2) { animation-delay: 4s; }
                        .fade-slide:nth-child(3) { animation-delay: 8s; }
                        .fade-slide:nth-child(4) { animation-delay: 12s; }
                        .fade-slide:nth-child(5) { animation-delay: 16s; }
                        .fade-slide:nth-child(6) { animation-delay: 20s; }
                        .fade-slide:nth-child(7) { animation-delay: 24s; }
                        .fade-slide:nth-child(8) { animation-delay: 28s; }
                        .fade-slide:nth-child(9) { animation-delay: 32s; }
                        .fade-slide:nth-child(10) { animation-delay: 36s; }
                        @keyframes fade { 0%, 100% { opacity: 0; } 10%, 33% { opacity: 1; } 43% { opacity: 0; } }
                    </style>
                    
                    <img src="assets/IMG_1291.jpg" class="fade-slide" alt="Action 1" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+1'">
                    <img src="assets/IMG_5330.jpg" class="fade-slide" alt="Action 2" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+2'">
                    <img src="assets/IMG_5527.jpg" class="fade-slide" alt="Action 3" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+3'">
                    <img src="assets/IMG_5555.jpg" class="fade-slide" alt="Action 4" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+4'">
                    <img src="assets/IMG_5597.jpg" class="fade-slide" alt="Action 5" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+5'">
                    <img src="assets/IMG_5632.jpg" class="fade-slide" alt="Action 6" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+6'">
                    <img src="assets/IMG_6424.jpg" class="fade-slide" alt="Action 7" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+7'">
                    <img src="assets/IMG_6287.jpg" class="fade-slide" alt="Action 8" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+8'">
                    <img src="assets/IMG_6206.jpg" class="fade-slide" alt="Action 9" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+9'">
                    <img src="assets/IMG_6702.jpg" class="fade-slide" alt="Action 10" onerror="this.src='https://via.placeholder.com/400x200?text=Highlight+10'">
                </div>
                <div style="padding: 10px; text-align: center; font-size: 11px; color: #777; font-style: italic; background: white;">
                    Live from the SUKMA venues
                </div>
            </div>

        </div>
    </div>
</div>

<div id="News" class="tab-content">
    <div class="generic-container" style="max-width: 800px; margin: 0 auto; background: transparent; box-shadow: none; padding: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0; color: #da251d;">Official News Feed</h3>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                <a href="add_news.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">+ Publish News</a>
            <?php endif; ?>
        </div>

        <?php 
        mysqli_data_seek($news_result, 0);
        if(mysqli_num_rows($news_result) > 0) {
            while($news = mysqli_fetch_assoc($news_result)) { 
                $news_id = $news['id'];
                
                $like_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM likes WHERE news_id = $news_id"))['total'];
                
                $user_id = $_SESSION['user_id'];
                $has_liked = (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM likes WHERE news_id = $news_id AND user_id = $user_id")) > 0);
                $like_btn_color = $has_liked ? "#0056b3" : "#6c757d";

                $is_hidden = ($news['status'] == 'hidden');
                $opacity = $is_hidden ? "0.6" : "1";
        ?>
            <div id="post-<?php echo $news_id; ?>" style="background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 25px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); opacity: <?php echo $opacity; ?>; transition: opacity 0.3s;">
                
                <div style="padding: 15px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin: 0 0 5px 0; color: #333; font-size: 18px;"><?php echo $news['title']; ?></h4>
                        <div style="font-size: 13px; color: #777;">
                            <strong><?php echo $news['author']; ?></strong> &bull; <?php echo date('d M Y, H:i', strtotime($news['created_at'])); ?>
                        </div>
                    </div>

                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                        <div class="dropdown">
                            <button onclick="toggleDropdown(<?php echo $news_id; ?>)" class="dropdown-btn">⋮</button>
                            <div id="dropdown-<?php echo $news_id; ?>" class="dropdown-content">
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    <button onclick="toggleHide(<?php echo $news_id; ?>, '<?php echo $news['status']; ?>')" id="hide-btn-<?php echo $news_id; ?>">
                                        <?php echo $is_hidden ? "👁️‍🗨️ Unhide Post" : "👁️ Hide Post"; ?>
                                    </button>
                                <?php endif; ?>
                                <a href="edit_news.php?id=<?php echo $news_id; ?>">✏️ Edit</a>
                                <button onclick="openDeleteModal(<?php echo $news_id; ?>)" style="color: #dc3545;">🗑️ Delete</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php 
                if (!empty($news['image_path'])) {
                    // Try to decode it to see if it is a new array
                    $images = json_decode($news['image_path'], true);
                    
                    echo '<div class="image-grid">';
                    
                    if (is_array($images) && count($images) > 0) {
                        // NEW POST: Loop through the array of images
                        foreach ($images as $img) {
                            echo '<img src="' . $img . '" alt="News Image">';
                        }
                    } else {
                        // OLD POST: It's just a single text string, print it directly
                        echo '<img src="' . $news['image_path'] . '" alt="News Image">';
                    }
                    
                    echo '</div>';
                }
                ?>

                <div style="padding: 15px;">
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #444; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </p>

                    <div style="display: flex; gap: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                        <button onclick="toggleLike(<?php echo $news_id; ?>)" id="like-btn-<?php echo $news_id; ?>" style="background: none; border: none; color: <?php echo $like_btn_color; ?>; cursor: pointer; font-weight: bold; font-size: 14px; padding: 0;">
                            👍 Like (<span id="like-count-<?php echo $news_id; ?>"><?php echo $like_count; ?></span>)
                        </button>
                        <button onclick="sharePost('<?php echo addslashes($news['title']); ?>', '<?php echo $news_id; ?>')" style="background: none; border: none; color: #6c757d; cursor: pointer; font-weight: bold; font-size: 14px; padding: 0;">
                            ↗️ Share
                        </button>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-top: 1px solid #eee;">
                    <h5 style="margin: 0 0 10px 0; color: #555;">Comments</h5>
                    
                    <div id="comment-list-<?php echo $news_id; ?>" style="max-height: 200px; overflow-y: auto; margin-bottom: 10px;">
                        <?php 
                        $comments_query = mysqli_query($conn, "SELECT username, comment_text, created_at FROM comments WHERE news_id = $news_id ORDER BY created_at ASC");
                        if(mysqli_num_rows($comments_query) > 0) {
                            while($comment = mysqli_fetch_assoc($comments_query)) {
                        ?>
                            <div style="margin-bottom: 8px; font-size: 13px;">
                                <strong style="color: #0056b3;"><?php echo $comment['username']; ?>:</strong> 
                                <span style="color: #444;"><?php echo htmlspecialchars($comment['comment_text']); ?></span>
                                <span style="color: #aaa; font-size: 11px; margin-left: 5px;"><?php echo date('d M, H:i', strtotime($comment['created_at'])); ?></span>
                                <button onclick="replyTo('<?php echo $comment['username']; ?>', <?php echo $news_id; ?>)" style="background: none; border: none; color: #da251d; font-size: 11px; cursor: pointer; margin-left: 5px;">Reply</button>
                            </div>
                        <?php } } else { echo "<div id='no-comment-{$news_id}' style='font-size: 13px; color: #777;'>Be the first to comment!</div>"; } ?>
                    </div>

                    <div style="display: flex; gap: 10px; margin: 0;">
                        <input type="text" id="comment-input-<?php echo $news_id; ?>" placeholder="Write a comment..." style="flex: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 20px; outline: none; margin: 0;">
                        <button onclick="postComment(<?php echo $news_id; ?>)" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-weight: bold; margin: 0;">Post</button>
                    </div>
                </div>

            </div>
        <?php } } else { echo "<p style='text-align:center; color:#777;'>No news available.</p>"; } ?>
    </div>
</div>

<div id="Events" class="tab-content">
    <div class="generic-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Tournament Schedule</h3>
            
            <div style="display: flex; gap: 15px; align-items: center;">

                <div class="dropdown">
                    <button onclick="toggleDropdown('filter')" class="dropdown-btn" style="background: #f8f9fa; border: 1px solid #ccc; padding: 8px 15px; border-radius: 20px; font-size: 14px; color: #333; cursor: pointer; white-space: nowrap; ">
                        ⚙️ Filter By ▾
                    </button>
                    <div id="dropdown-filter" class="dropdown-content" style="padding: 15px; width: 250px; left: auto; right: 0; cursor: default;">
                        
                        <label style="font-size: 12px; font-weight: bold; color: #666;">Sport</label>
                        <select id="filter_sport" onchange="applyAdvancedFilters()" style="width: 100%; margin-bottom: 15px; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Sports</option>
                            <?php 
                            mysqli_data_seek($sports_query, 0);
                            while($sport = mysqli_fetch_assoc($sports_query)) {
                                echo "<option value='" . htmlspecialchars($sport['sport_name']) . "'>" . $sport['sport_name'] . "</option>";
                            }
                            ?>
                        </select>

                        <label style="font-size: 12px; font-weight: bold; color: #666;">Match Phase</label>
                        <select id="filter_phase" onchange="applyAdvancedFilters()" style="width: 100%; margin-bottom: 15px; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Phases</option>
                            <?php 
                            mysqli_data_seek($phases_query, 0);
                            while($phase = mysqli_fetch_assoc($phases_query)) {
                                echo "<option value='" . htmlspecialchars($phase['phase_name']) . "'>" . $phase['phase_name'] . "</option>";
                            }
                            ?>
                        </select>

                        <label style="font-size: 12px; font-weight: bold; color: #666;">Venue</label>
                        <select id="filter_venue" onchange="applyAdvancedFilters()" style="width: 100%; margin-bottom: 15px; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Venues</option>
                            <?php 
                            mysqli_data_seek($venues_query, 0);
                            while($venue = mysqli_fetch_assoc($venues_query)) {
                                echo "<option value='" . htmlspecialchars($venue['venue_name']) . "'>" . $venue['venue_name'] . "</option>";
                            }
                            ?>
                        </select>

                        <label style="font-size: 12px; font-weight: bold; color: #666;">Date</label>
                        <input type="date" id="filter_date" onchange="applyAdvancedFilters()" style="width: 100%; margin-bottom: 15px; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">

                        <button type ="button" onclick="clearFilters()" style="width: 100%; padding: 8px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Reset Filters</button>
                    </div>
                </div>

                <input type="text" id="eventSearchInput" onkeyup="applyAdvancedFilters()" placeholder="🔍 Search events, venues, states..." style="white-space: nowrap;">
                
                <button id="toggleCompletedBtn" onclick="toggleCompleted()" style="background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; white-space: nowrap;">
                    👁️ Hide Completed
                </button>
                
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    <button onclick="openAddEventModal()" style="background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; white-space: nowrap;">+ Add Event</button>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th style="min-width: 250px;">Contender / Venue</th>
                    <th class="center">Date</th>
                    
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                        <th class="center">Action</th>
                    <?php else: ?>
                        <th class="center">Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="events-table-body">
                <?php 
                    mysqli_data_seek($events_result, 0);
                    $has_shown_divider = false; 

                    while($event = mysqli_fetch_assoc($events_result)) { 
        
                        // --- 1. NEW MERGED PARTICIPANT & VENUE LOGIC ---
                        $participants_html = "<span style='color:#aaa;'>TBD</span>; justify-content: center;";
        
                        if (!empty($event['participating_states'])) {
                        $states_array = json_decode($event['participating_states'], true);
                            if (is_array($states_array) && count($states_array) > 0) {
                
                                // Start the flex container for the flags
                                $participants_html = "<div style='text-align: center; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: center; margin-bottom: 8px;'>";

                                if (count($states_array) == 2) {
                                    // HEAD-TO-HEAD LAYOUT (Your first wireframe row)
                                    $s1 = $states_array[0];
                                    $img1 = strtolower(str_replace(' ', '_', $s1)) . '.png';
                                    $s2 = $states_array[1];
                                    $img2 = strtolower(str_replace(' ', '_', $s2)) . '.png';

                                    $participants_html .= "
                                        <div style='text-align: center;'>
                                            <img src='assets/flags/{$img1}' style='width:35px; height:35px; border-radius:50%; object-fit:cover; border:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.1);' title='{$s1}'><br>
                                            <span style='font-size: 11px; font-weight: bold; color: #da251d;'>{$s1}</span>
                                        </div>
                                        <span style='font-size:12px; font-weight:bold; color:#777;'>VS</span>
                                        <div style='text-align: center;'>
                                            <img src='assets/flags/{$img2}' style='width:35px; height:35px; border-radius:50%; object-fit:cover; border:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.1);' title='{$s2}'><br>
                                            <span style='font-size: 11px; font-weight: bold; color: #0056b3;'>{$s2}</span>
                                        </div>";
                                } 
                                else {
                                    // GROUP STAGE LAYOUT (Your second wireframe row)
                                    foreach($states_array as $s) {
                                        $img = strtolower(str_replace(' ', '_', $s)) . '.png';
                                        $participants_html .= "
                                        <div style='text-align: center; width: 45px;'>
                                            <img src='assets/flags/{$img}' style='width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.1);' title='{$s}'><br>
                                            <span style='font-size: 9px; color: #555; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;'>{$s}</span>
                                        </div>";
                                    }
                                }
                        $participants_html .= "</div>";

                        // Add the Venue neatly underneath the flags!
                        // NEW: View Athletes Button
                        $participants_html .= "<div style='text-align: center; font-size: 12px; color: #666; border-top: 1px dashed #eee; padding-top: 5px;'><span style='color: #da251d;'>📍</span> " . htmlspecialchars($event['venue']) . "</div>";
            }
        }

        // --- 2. THE DIVIDER ROW (Updated colspan to 4!) ---
        if ($event['match_status'] == 'Completed' && !$has_shown_divider) {
            echo "<tr class='completed-divider' style='background: #e9ecef; border-top: 2px solid #ccc; border-bottom: 2px solid #ccc;'>
                    <td colspan='4' style='text-align: center; font-weight: bold; color: #6c757d; padding: 10px; letter-spacing: 2px; font-size: 12px;'>
                        ⬇️ COMPLETED MATCHES ⬇️
                    </td>
                  </tr>";
            $has_shown_divider = true;
        }

        $opacity = ($event['match_status'] == 'Completed') ? '0.6' : '1.0'; 
        $bg_color = ($event['match_status'] == 'Completed') ? '#fcfcfc' : '#ffffff';
    ?>
    
    <tr id="event-row-<?php echo $event['id']; ?>" class="schedule-row" data-status="<?php echo $event['match_status']; ?>" style="opacity: <?php echo $opacity; ?>; background-color: <?php echo $bg_color; ?>;">
        
        <td id="td-name-<?php echo $event['id']; ?>" data-match-phase="<?php echo htmlspecialchars($event['match_phase']); ?>">
            <strong><?php echo $event['event_name']; ?></strong><br>
            <span style='font-size:12px; color:#666;'>➔ <?php echo $event['match_phase']; ?></span>
        </td>
        
        <td id="td-participants-<?php echo $event['id']; ?>" style="line-height: 1.4;">
            <?php echo $participants_html; ?>
        </td>
        
                    <td id="td-date-<?php echo $event['id']; ?>" class="center" data-raw-date="<?php echo $event['event_date']; ?>">
                        <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                    </td>
        
                    <!-- ACTION / STATUS COLUMN -->
        <td class="center" style="white-space: nowrap;">
            
            <?php if($event['match_status'] == 'Completed'): ?>
                
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    
                    <button onclick="openParticipantsModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['event_name']); ?>', '<?php echo addslashes($event['match_phase']); ?>')" style="background: none; border: none; cursor: pointer; color: #0056b3; font-size: 18px; margin-right: 8px; vertical-align: middle; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="View Athletes">👥</button>

                    <button onclick="openSetResultModal(<?php echo $event['id']; ?>)" style="background: #fff; border: 1px solid #ddd; padding: 8px 15px; border-radius: 25px; display: inline-block; font-size: 15px; font-weight: bold; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.2s; vertical-align: middle;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" title="Click to Edit Results">
                        <?php 
                            if($event['gold_winner']) echo "<span style='color: #d4af37;'>🥇 " . substr(strtoupper($event['gold_winner']), 0, 3) . "</span>";
                            if($event['silver_winner']) echo "<span style='color: #9e9e9e; margin-left: 8px;'>| 🥈 " . substr(strtoupper($event['silver_winner']), 0, 3) . "</span>";
                            if($event['bronze_winner']) echo "<span style='color: #cd7f32; margin-left: 8px;'>| 🥉 " . substr(strtoupper($event['bronze_winner']), 0, 3) . "</span>";
                            if(!$event['gold_winner'] && !$event['silver_winner']) echo "<span style='color: #28a745;'>✓ Finished</span>";
                        ?>
                        <span style="font-size: 12px; margin-left: 8px; color: #17a2b8;">✏️</span>
                    </button>
                <?php else: ?>
                    <div style="background: #fff; border: 1px solid #ddd; padding: 8px 15px; border-radius: 25px; display: inline-block; font-size: 15px; font-weight: bold; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                        <?php 
                        if($event['gold_winner']) echo "<span style='color: #d4af37;'>🥇 " . substr(strtoupper($event['gold_winner']), 0, 3) . "</span>";
                        if($event['silver_winner']) echo "<span style='color: #9e9e9e; margin-left: 8px;'>| 🥈 " . substr(strtoupper($event['silver_winner']), 0, 3) . "</span>";
                        if($event['bronze_winner']) echo "<span style='color: #cd7f32; margin-left: 8px;'>| 🥉 " . substr(strtoupper($event['bronze_winner']), 0, 3) . "</span>";
                        if(!$event['gold_winner'] && !$event['silver_winner']) echo "<span style='color: #28a745;'>✓ Finished</span>";
                        ?>
                    </div>
                <?php endif; ?>

                <?php else: ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                    <button onclick="openParticipantsModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['event_name']); ?>', '<?php echo addslashes($event['match_phase']); ?>')" style="background: none; border: none; cursor: pointer; color: #0056b3; font-size: 20px; margin-right: 12px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="View Athletes">👥</button>

                    <button onclick="openSetResultModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #d4af37; font-size: 24px; margin-right: 12px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Set Result">🏆</button>
                    <button onclick="openEditEventModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #17a2b8; font-size: 20px; margin-right: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Edit">✏️</button>
                    <button onclick="openDeleteEventModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 20px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Delete">🗑️</button>
                <?php else: ?>
                    <span style="color: #888; font-size: 14px; font-style: italic;">Upcoming</span>
                <?php endif; ?>

            <?php endif; ?>
            
        </td>
                   
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'tab_athletes.php'; ?>

<?php if($_SESSION['role'] == 'admin'): ?>
<div id="ManageUsers" class="tab-content">
    <div class="dashboard-wrapper">
        <div class="side-panel">
            <div class="generic-container">
                <h3>Provision New Account</h3>
                <?php echo $user_msg; ?>
                <form method="POST">
                    <input type="hidden" name="add_user" value="1">
                    <label>Username</label><input type="text" name="username" required>
                    <label>Email</label><input type="email" name="email" required>
                    <label>Password</label><input type="password" name="password" required>
                    <label>Assign Role</label>
                    <select name="role" required>
                        <option value="staff">Staff / Official</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>
        </div>
        <div class="center-panel">
            <h3>System Access List</h3>
            <table>
                <thead><tr><th>Username</th><th>Role</th><th class="center">Joined</th></tr></thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($users_result, 0);
                    while($user = mysqli_fetch_assoc($users_result)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo strtoupper($user['role']); ?></td>
                        <td class="center"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'dashboard_modals.php'; ?>

<script>
    function openTab(evt, tabName) {
        // Save the active tab to localStorage so it survives a page refresh
        localStorage.setItem('activeTab', tabName);

        var i, tabcontent, chrometabs;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        chrometabs = document.getElementsByClassName("chrome-tab");
        for (i = 0; i < chrometabs.length; i++) {
            chrometabs[i].className = chrometabs[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        
        // If triggered by a click, add active class to the button.
        if (evt) {
            // Find the correct button by matching text content or tab name, 
            // since sidebar clicks don't originate from the tab bar.
            for (i = 0; i < chrometabs.length; i++) {
                if(chrometabs[i].getAttribute("onclick").includes(tabName)) {
                    chrometabs[i].className += " active";
                }
            }
        }
    }

    // Auto-open the correct tab on page load
    window.onload = function() {
        var activeTab = localStorage.getItem('activeTab');
        if (activeTab && document.getElementById(activeTab)) {
            openTab(null, activeTab);
        } else {
            // Default to Dashboard
            openTab(null, 'Dashboard');
        }
    };

    // --- DYNAMIC ANALYTICS ENGINE ---
    let medalChartInstance = null;
    const ctx = document.getElementById('medalChart').getContext('2d');
    
    // 1. We grab ALL the state data from PHP instantly!
    const allStatesData = <?php echo json_encode($all_states); ?>;
    
    // 2. We grab the Top 5 Data for the default view
    const defaultLabels = <?php echo json_encode($states); ?>;
    const defaultGolds = <?php echo json_encode($golds); ?>;
    const defaultSilvers = <?php echo json_encode($silvers); ?>;
    const defaultBronzes = <?php echo json_encode($bronzes); ?>;

    function initDefaultBarChart() {
        if(medalChartInstance) medalChartInstance.destroy();
        document.getElementById('analytics_chart_title').innerText = "📊 Top 5 Contingents (Gold-Weighted)";
        
        medalChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: defaultLabels,
                datasets: [
                    { label: 'Gold', data: defaultGolds, backgroundColor: 'rgba(255, 193, 7, 0.8)', borderColor: '#d4af37', borderWidth: 1 },
                    { label: 'Silver', data: defaultSilvers, backgroundColor: 'rgba(192, 192, 192, 0.8)', borderColor: '#9e9e9e', borderWidth: 1 },
                    { label: 'Bronze', data: defaultBronzes, backgroundColor: 'rgba(205, 127, 50, 0.8)', borderColor: '#cd7f32', borderWidth: 1 }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
            }
        });
    }

    // 3. The Function triggered by the Dropdown
    function updateAnalyticsChart() {
        let selectedState = document.getElementById('analytics_state_filter').value;
        
        document.getElementById('analytics_sport_filter').value = 'ALL';
        // If they select "Overview", go back to the Bar Chart
        if (selectedState === 'ALL') {
            initDefaultBarChart();
            return;
        }

        // Otherwise, build a Doughnut Chart for the specific state
        if(medalChartInstance) medalChartInstance.destroy();
        document.getElementById('analytics_chart_title').innerText = "🎯 Performance Deep Dive: " + selectedState;
        
        let stateStats = allStatesData[selectedState];
        
        medalChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Gold Medals', 'Silver Medals', 'Bronze Medals'],
                datasets: [{
                    data: [stateStats.gold, stateStats.silver, stateStats.bronze],
                    backgroundColor: ['#d4af37', '#9e9e9e', '#cd7f32'],
                    borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    // Initialize the default view when the page loads
    initDefaultBarChart();

// 4. The Sport-Specific Dominance Function
    function updateSportDominance() {
        let selectedSport = document.getElementById('analytics_sport_filter').value;
        
        // Reset the State dropdown so they don't conflict!
        document.getElementById('analytics_state_filter').value = 'ALL';
        
        if (selectedSport === 'ALL') {
            initDefaultBarChart();
            return;
        }

        document.getElementById('analytics_chart_title').innerText = "🏆 Dominance: " + selectedSport;
        
        // Fetch the sport-specific data from our new PHP endpoint
        fetch('event_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'fetch_sport_dominance', sport: selectedSport })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if(medalChartInstance) medalChartInstance.destroy();
                
                let labels = [];
                let golds = [];
                let silvers = [];
                let bronzes = [];
                
                data.standings.forEach(row => {
                    labels.push(row.state_name);
                    golds.push(row.gold_count);
                    silvers.push(row.silver_count);
                    bronzes.push(row.bronze_count);
                });

                // Safety fallback: If no one has won medals in this sport yet
                if(labels.length === 0) {
                    labels = ['No Completed Matches Yet'];
                    golds = [0]; silvers = [0]; bronzes = [0];
                }

                medalChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Gold', data: golds, backgroundColor: 'rgba(255, 193, 7, 0.8)', borderColor: '#d4af37', borderWidth: 1 },
                            { label: 'Silver', data: silvers, backgroundColor: 'rgba(192, 192, 192, 0.8)', borderColor: '#9e9e9e', borderWidth: 1 },
                            { label: 'Bronze', data: bronzes, backgroundColor: 'rgba(205, 127, 50, 0.8)', borderColor: '#cd7f32', borderWidth: 1 }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
                    }
                });
            }
        });
    }

// 5. The Efficiency Ratio Metric (Mixed Chart with Dual Y-Axes)
    function viewEfficiency() {
        // Reset the dropdowns so the UI reflects the current state
        document.getElementById('analytics_sport_filter').value = 'ALL';
        document.getElementById('analytics_state_filter').value = 'ALL';
        
        document.getElementById('analytics_chart_title').innerText = "⚖️ Athlete-to-Medal Efficiency (ROI)";
        
        fetch('event_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'fetch_efficiency' })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if(medalChartInstance) medalChartInstance.destroy();
                
                let labels = [];
                let athletes = [];
                let medals = [];
                let ratios = [];
                
                data.data.forEach(row => {
                    // Only show states that actually brought athletes
                    if(row.athletes > 0) { 
                        // Add the % to the label for quick reading
                        labels.push(row.state + " (" + row.ratio + "%)");
                        athletes.push(row.athletes);
                        medals.push(row.medals);
                        ratios.push(row.ratio);
                    }
                });

                medalChartInstance = new Chart(ctx, {
                    type: 'bar', // Base type
                    data: {
                        labels: labels,
                        datasets: [
                            { 
                                type: 'line', // The Overlay Line
                                label: 'Total Medals Won', 
                                data: medals, 
                                borderColor: '#d4af37', 
                                backgroundColor: '#d4af37', 
                                borderWidth: 3,
                                tension: 0.3,
                                yAxisID: 'y1' // Assign to the RIGHT axis
                            },
                            { 
                                type: 'bar', // The Background Bars
                                label: 'Total Athletes Sent', 
                                data: athletes, 
                                backgroundColor: 'rgba(0, 86, 179, 0.2)', 
                                borderColor: '#0056b3', 
                                borderWidth: 1,
                                yAxisID: 'y' // Assign to the LEFT axis
                            }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: { 
                            y: { 
                                type: 'linear', 
                                display: true, 
                                position: 'left',
                                title: { display: true, text: 'Number of Athletes' }
                            },
                            y1: { 
                                type: 'linear', 
                                display: true, 
                                position: 'right',
                                title: { display: true, text: 'Medals Won' },
                                grid: { drawOnChartArea: false } // Prevent gridlines from crossing over each other
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    afterBody: function(context) {
                                        // Find the index of the hovered item to inject the custom ratio text
                                        let index = context[0].dataIndex;
                                        return '\nEfficiency Ratio: ' + ratios[index] + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    // --- SOCIAL MEDIA AJAX FUNCTIONS ---

// 1. Toggle Like (No Refresh)
function toggleLike(newsId) {
    fetch('news_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'like', news_id: newsId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('like-count-' + newsId).innerText = data.likes;
            document.getElementById('like-btn-' + newsId).style.color = data.has_liked ? "#0056b3" : "#6c757d";
        }
    });
}

// 2. Post Comment (No Refresh)
function postComment(newsId) {
    let inputField = document.getElementById('comment-input-' + newsId);
    let commentText = inputField.value.trim();
    
    if(commentText === "") return; // Don't post empty comments

    fetch('news_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'comment', news_id: newsId, comment_text: commentText })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Remove "Be the first" text if it exists
            let noCommentMsg = document.getElementById('no-comment-' + newsId);
            if(noCommentMsg) noCommentMsg.remove();
            
            // Instantly add the comment to the screen
            document.getElementById('comment-list-' + newsId).innerHTML += data.html;
            inputField.value = ""; // Clear the input box
            
            // Scroll to bottom of comments
            let commentList = document.getElementById('comment-list-' + newsId);
            commentList.scrollTop = commentList.scrollHeight;
        }
    });
}

// 3. Reply to Comment (Fills the input box)
function replyTo(username, newsId) {
    let inputField = document.getElementById('comment-input-' + newsId);
    inputField.value = "@" + username + " ";
    inputField.focus(); // Automatically put the user's cursor in the box
}

// 4. Toggle Admin Hide (No Refresh)
function toggleHide(newsId, currentStatus) {
    fetch('news_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_status', news_id: newsId, current_status: currentStatus })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            let post = document.getElementById('post-' + newsId);
            let icon = document.getElementById('eye-icon-' + newsId);
            
            if(data.new_status === 'hidden') {
                post.style.opacity = "0.6";
                icon.innerText = "👁️‍🗨️";
            } else {
                post.style.opacity = "1";
                icon.innerText = "👁️";
            }
            // Update the onclick function so it works if clicked again
            post.querySelector("button[onclick^='toggleHide']").setAttribute("onclick", `toggleHide(${newsId}, '${data.new_status}')`);
        }
    });
}

// 5. Share Button (Native Web Share API)
function sharePost(title, newsId) {
    // We create a fake URL to share, since this is a local server
    let shareUrl = window.location.href.split('#')[0] + "?news_id=" + newsId;
    
    if (navigator.share) {
        navigator.share({
            title: 'SUKMA 2026 Update',
            text: title,
            url: shareUrl
        }).catch(console.error);
    } else {
        // Fallback for desktop browsers that don't support Native Share
        navigator.clipboard.writeText(shareUrl);
        alert("Link copied to clipboard! You can paste it into WhatsApp.");
    }
}

// 6. Delete Post (With Confirmation & No Refresh)
function deletePost(newsId) {
    // Built-in browser confirmation popup
    if(confirm("Are you sure you want to permanently delete this announcement?")) {
        fetch('news_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_post', news_id: newsId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                // Find the post container and add a smooth fade-out animation before removing it
                let postElement = document.getElementById('post-' + newsId);
                postElement.style.opacity = '0';
                setTimeout(() => { postElement.remove(); }, 300); // Wait 0.3s for animation to finish
            }
        });
    }
}

// Dropdown Logic
function toggleDropdown(id) {
    document.getElementById("dropdown-" + id).classList.toggle("show");
}
// Close dropdowns if clicked outside
window.onclick = function(event) {
    // NEW FIX: If you click anywhere INSIDE a dropdown menu, ignore the click and let the buttons work!
    if (event.target.closest('.dropdown-content')) {
        return; 
    }

    // If you click completely outside, close all open dropdowns
    if (!event.target.matches('.dropdown-btn')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            if (dropdowns[i].classList.contains('show')) {
                dropdowns[i].classList.remove('show');
            }
        }
    }
}

// Custom Modal Logic
let postToDelete = null;

function openDeleteModal(newsId) {
    postToDelete = newsId;
    document.getElementById('customDeleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    postToDelete = null;
    document.getElementById('customDeleteModal').style.display = 'none';
}

// The actual delete execution
document.getElementById('confirmDeleteBtn').onclick = function() {
    if(!postToDelete) return;
    
    fetch('news_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_post', news_id: postToDelete })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            let postElement = document.getElementById('post-' + postToDelete);
            postElement.style.opacity = '0';
            setTimeout(() => { postElement.remove(); }, 300);
            closeDeleteModal();
        }
    });
};

// --- ADD EVENT MODAL & AJAX LOGIC ---
function openAddEventModal() {
    document.getElementById('addEventModal').style.display = 'flex';
}

function closeAddEventModal() {
    document.getElementById('addEventModal').style.display = 'none';
    document.getElementById('addEventForm').reset(); // Clear form
}

// THE ULTIMATE UNIFIED EVENT SUBMITTER
function submitNewEvent(event) {
    event.preventDefault(); // Stop default form submit

    let eventName = document.getElementById('modal_event_name').value;
    let venue = document.getElementById('modal_venue').value;
    let eventDate = document.getElementById('modal_event_date').value;
    let matchPhase = document.getElementById('modal_match_phase').value;

    let participatingStates = [];
    let sportSelect = document.getElementById('modal_event_name');
    let format = sportSelect.options[sportSelect.selectedIndex].getAttribute('data-format');

    if (format === 'h2h') {
        participatingStates.push(document.getElementById('state_a').value);
        participatingStates.push(document.getElementById('state_b').value);
    } else if (format === 'group') {
        let checkboxes = document.querySelectorAll('input[name="group_states"]:checked');
        checkboxes.forEach(cb => participatingStates.push(cb.value));
        if(participatingStates.length === 0) {
            alert("Please select at least one participating state.");
            return;
        }
    }

    // Explicitly package ALL required data so PHP never throws a warning
    let payload = {
        action: 'add_event',
        event_name: eventName,
        venue: venue,
        event_date: eventDate,
        match_phase: matchPhase,
        states: participatingStates,
        format_type: format 
    };

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.text()) // Read as raw text to prevent silent crashes
    .then(text => {
        try {
            let data = JSON.parse(text); // Try to parse it cleanly
            if(data.status === 'success') {
                closeAddEventModal();
                location.reload(); // Instantly refresh to show the new event + Action buttons!
            } else {
                alert('Error saving event: ' + data.message);
            }
        } catch (error) {
            // If PHP throws a hidden warning, JS catches it here and safely refreshes anyway!
            console.log("Safe Catch Triggered. Server responded with:", text);
            closeAddEventModal();
            location.reload(); 
        }
    });
}

// Array of all 14 Malaysian Contingents
const allStates = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 
    'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 
    'Terengganu'
];

// Function to make the form shape-shift
function renderStateInputs() {
    let sportSelect = document.getElementById('modal_event_name');
    // Get the data-format of the currently selected option
    let format = sportSelect.options[sportSelect.selectedIndex].getAttribute('data-format');
    let container = document.getElementById('dynamic-state-inputs');
    
    container.innerHTML = ''; // Clear out the old inputs
    
    if (!format) {
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';

    if (format === 'h2h') {
        // Head-to-Head: Create 2 Dropdowns
        container.innerHTML = `
            <label style="font-weight:bold; font-size:13px; color:#da251d;">Versus Match (Head-to-Head)</label>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <select id="state_a" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="" disabled selected>State A</option>
                    ${allStates.map(s => `<option value="${s}">${s}</option>`).join('')}
                </select>
                <span style="font-weight:bold; align-self:center;">VS</span>
                <select id="state_b" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="" disabled selected>State B</option>
                    ${allStates.map(s => `<option value="${s}">${s}</option>`).join('')}
                </select>
            </div>
        `;
    } else if (format === 'group') {
        // Group/Mass: Create 14 Checkboxes with fixed CSS overrides
        let checkboxesHTML = allStates.map(s => `
            <label style="display:flex; align-items:center; font-size:14px; cursor:pointer; padding:5px 0;">
                <input type="checkbox" name="group_states" value="${s}" style="width: auto; margin: 0 10px 0 0; transform: scale(1.2); cursor: pointer;"> 
                <span style="line-height: 1.2;">${s}</span>
            </label>
        `).join('');
        
        container.innerHTML = `
            <label style="font-weight:bold; font-size:13px; color:#da251d; display:block; margin-bottom:10px;">Group/Heat Participants</label>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px 15px;">
                ${checkboxesHTML}
            </div>
        `;
    }
}

// --- CUSTOM DELETE EVENT LOGIC ---
let eventToDelete = null;

function openDeleteEventModal(eventId) {
    eventToDelete = eventId;
    document.getElementById('customDeleteEventModal').style.display = 'flex';
}

function closeDeleteEventModal() {
    eventToDelete = null;
    document.getElementById('customDeleteEventModal').style.display = 'none';
}

// The actual execution when the user clicks the red confirm button
document.getElementById('confirmDeleteEventBtn').onclick = function() {
    if(!eventToDelete) return;
    
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_event', event_id: eventToDelete })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Smoothly fade out the row and remove it
            let row = document.getElementById('event-row-' + eventToDelete);
            row.style.opacity = '0';
            setTimeout(() => { row.remove(); }, 300);
            
            closeDeleteEventModal(); // Hide the modal
        } else {
            alert('Error deleting event.');
        }
    });
};

// --- EDIT EVENT ---
function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
}

function openEditEventModal(eventId) {
    // 1. Fetch the existing data
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_event', event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // 2. Populate basic fields
            document.getElementById('edit_event_id').value = data.id;
            document.getElementById('edit_modal_event_name').value = data.event_name;
            document.getElementById('edit_modal_venue').value = data.venue;
            document.getElementById('edit_modal_event_date').value = data.event_date;
            document.getElementById('edit_modal_match_phase').value = data.match_phase;
            
            // 3. Trigger shape-shifting UI to build the inputs
            renderEditStateInputs(data.states, data.format_type);
            
            // 4. Show the modal
            document.getElementById('editEventModal').style.display = 'flex';
        }
    });
}

function renderEditStateInputs(existingStates = [], preloadedFormat = null) {
    let sportSelect = document.getElementById('edit_modal_event_name');
    let format = preloadedFormat || sportSelect.options[sportSelect.selectedIndex].getAttribute('data-format');
    let container = document.getElementById('edit-dynamic-state-inputs');
    
    container.innerHTML = ''; 
    if (!format) { container.style.display = 'none'; return; }
    container.style.display = 'block';

    if (format === 'h2h') {
        container.innerHTML = `
            <label style="font-weight:bold; font-size:13px; color:#0056b3;">Versus Match (Head-to-Head)</label>
            <div style="display:flex; gap:10px; margin-top:5px;">
                <select id="edit_state_a" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="" disabled>State A</option>
                    ${allStates.map(s => `<option value="${s}" ${existingStates[0] === s ? 'selected' : ''}>${s}</option>`).join('')}
                </select>
                <span style="font-weight:bold; align-self:center;">VS</span>
                <select id="edit_state_b" required style="flex:1; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="" disabled>State B</option>
                    ${allStates.map(s => `<option value="${s}" ${existingStates[1] === s ? 'selected' : ''}>${s}</option>`).join('')}
                </select>
            </div>
        `;
    } else if (format === 'group') {
        let checkboxesHTML = allStates.map(s => `
            <label style="display:flex; align-items:center; font-size:14px; cursor:pointer; padding:5px 0;">
                <input type="checkbox" name="edit_group_states" value="${s}" ${existingStates.includes(s) ? 'checked' : ''} style="width: auto; margin: 0 10px 0 0; transform: scale(1.2); cursor: pointer;"> 
                <span style="line-height: 1.2;">${s}</span>
            </label>
        `).join('');
        
        container.innerHTML = `
            <label style="font-weight:bold; font-size:13px; color:#0056b3; display:block; margin-bottom:10px;">Group/Heat Participants</label>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px 15px;">
                ${checkboxesHTML}
            </div>
        `;
    }
}

function submitEditEvent(event) {
    event.preventDefault();

    let eventId = document.getElementById('edit_event_id').value;
    let eventName = document.getElementById('edit_modal_event_name').value;
    let venue = document.getElementById('edit_modal_venue').value;
    let eventDate = document.getElementById('edit_modal_event_date').value;
    let matchPhase = document.getElementById('edit_modal_match_phase').value;

    let participatingStates = [];
    let sportSelect = document.getElementById('edit_modal_event_name');
    let format = sportSelect.options[sportSelect.selectedIndex].getAttribute('data-format');

    if (format === 'h2h') {
        participatingStates.push(document.getElementById('edit_state_a').value);
        participatingStates.push(document.getElementById('edit_state_b').value);
    } else if (format === 'group') {
        let checkboxes = document.querySelectorAll('input[name="edit_group_states"]:checked');
        checkboxes.forEach(cb => participatingStates.push(cb.value));
        if(participatingStates.length === 0) {
            alert("Please select at least one participating state.");
            return;
        }
    }

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'edit_event', 
            event_id: eventId,
            event_name: eventName, 
            venue: venue, 
            event_date: eventDate,
            match_phase: matchPhase,
            states: participatingStates,
            format_type: format
        })
    })
    .then(response => response.text())
    .then(text => {
        try {
            let data = JSON.parse(text);
            if(data.status === 'success') {
                closeEditEventModal();
                location.reload();
            } else {
                alert('Error updating event.');
            }
        } catch(e) {
            console.log("Safe Catch Triggered. Server responded with:", text);
            closeEditEventModal();
            location.reload();
        }
    });
}

// --- NEW FEATURE: TOGGLE COMPLETED VISIBILITY ---
let isCompletedHidden = false;

function toggleCompleted() {
    isCompletedHidden = !isCompletedHidden; // Flip the switch
    let btn = document.getElementById('toggleCompletedBtn');
    
    if (isCompletedHidden) {
        btn.innerHTML = "👁️‍🗨️ Show Completed";
        btn.style.background = "#17a2b8"; // Turn blue when active
    } else {
        btn.innerHTML = "👁️ Hide Completed";
        btn.style.background = "#6c757d"; // Back to grey
    }
    
    applyAdvancedFilters(); // Immediately refresh the table
}

// --- UPGRADED ADVANCED COMPOUND FILTER LOGIC ---
function applyAdvancedFilters() {
    let searchInput = document.getElementById("eventSearchInput");
    let sportInput = document.getElementById("filter_sport");
    let venueInput = document.getElementById("filter_venue");
    let dateInput = document.getElementById("filter_date");
    let phaseInput = document.getElementById("filter_phase"); 

    let searchVal = searchInput ? searchInput.value.toLowerCase() : "";
    let sportVal = sportInput ? sportInput.value.toLowerCase() : "";
    let venueVal = venueInput ? venueInput.value.toLowerCase() : "";
    let dateVal = dateInput ? dateInput.value : "";
    let phaseVal = phaseInput ? phaseInput.value.toLowerCase() : ""; 
    
    // Check if ANY filter is currently being used
    let hasActiveFilters = (searchVal !== "" || sportVal !== "" || venueVal !== "" || dateVal !== "" || phaseVal !== "");

    let tableBody = document.getElementById("events-table-body");
    let rows = tableBody.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        
        // 1. Divider Logic: Hide the divider if filters are active OR if toggle is on
        if (rows[i].classList.contains('completed-divider')) {
            if (isCompletedHidden || hasActiveFilters) {
                rows[i].style.display = "none";
            } else {
                rows[i].style.display = "";
            }
            continue; // Skip the rest of the loop for the divider row!
        }

        // 2. Normal Row Filtering
        let cols = rows[i].getElementsByTagName("td");
        if (cols.length > 2) { 
            let rowText = rows[i].innerText.toLowerCase(); 
            let colSport = cols[0].innerText.toLowerCase(); 
            let colVenue = cols[1].innerText.toLowerCase(); 
            
            let colDateRaw = cols[2].getAttribute("data-raw-date") || ""; 
            let colPhaseRaw = cols[0].getAttribute("data-match-phase") || ""; 
            let colStatusRaw = rows[i].getAttribute("data-status") || ""; // Grab the completion status
            colPhaseRaw = colPhaseRaw.toLowerCase(); 

            let matchesSearch = (searchVal === "" || rowText.indexOf(searchVal) > -1);
            let matchesSport = (sportVal === "" || colSport.indexOf(sportVal) > -1);
            let matchesVenue = (venueVal === "" || colVenue.indexOf(venueVal) > -1);
            let matchesDate = (dateVal === "" || colDateRaw === dateVal);
            let matchesPhase = (phaseVal === "" || colPhaseRaw === phaseVal); 
            
            // NEW: Does this row violate the Hide switch?
            let isRowHiddenByToggle = (isCompletedHidden && colStatusRaw === "Completed");

            // Show only if it passes all active filters AND the hide toggle
            if (matchesSearch && matchesSport && matchesVenue && matchesDate && matchesPhase && !isRowHiddenByToggle) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

function clearFilters() {
    // Safely clear all inputs
    if(document.getElementById("eventSearchInput")) document.getElementById("eventSearchInput").value = "";
    if(document.getElementById("filter_sport")) document.getElementById("filter_sport").value = "";
    if(document.getElementById("filter_venue")) document.getElementById("filter_venue").value = "";
    if(document.getElementById("filter_date")) document.getElementById("filter_date").value = ""; 
    if(document.getElementById("filter_phase")) document.getElementById("filter_phase").value = ""; // <-- NEW
    
    applyAdvancedFilters();
    
    let dropdown = document.getElementById("dropdown-filter");
    if(dropdown) dropdown.classList.remove("show");
}
// --- UPDATED BULLETPROOF SET RESULT MODAL SHAPE-SHIFTER ---
function openSetResultModal(eventId) {
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_event', event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('result_event_id').value = data.id;
            document.getElementById('result_event_info').innerHTML = `${data.event_name} <br><span style='font-size:12px; color:#666;'>➔ ${data.match_phase}</span>`;
            
            // Build the dropdown options with a smart fallback
            let optionsHtml = '<option value="">-- Select Winner --</option>';
            let stateListToUse = (data.states && data.states.length > 0) ? data.states : allStates;
            
            stateListToUse.forEach(state => {
                let normalizedState = state.toUpperCase(); 
                optionsHtml += `<option value="${normalizedState}">${state}</option>`;
            });

            document.getElementById('result_gold').innerHTML = optionsHtml;
            document.getElementById('result_silver').innerHTML = optionsHtml;
            document.getElementById('result_bronze').innerHTML = optionsHtml;

            // Force the fetched database strings to uppercase before trying to match the options
            if (data.gold_winner) document.getElementById('result_gold').value = data.gold_winner.toUpperCase();
            if (data.silver_winner) document.getElementById('result_silver').value = data.silver_winner.toUpperCase();
            if (data.bronze_winner) document.getElementById('result_bronze').value = data.bronze_winner.toUpperCase();

            // --- DEFENSIVE PROGRAMMING SAFETY CHECK ---
            let wMedals = document.getElementById('wrapper_medals');
            let wGS = document.getElementById('input_group_gold_silver');
            let wBronze = document.getElementById('input_group_bronze');
            let wGeneric = document.getElementById('wrapper_generic');

            if (!wMedals || !wGS || !wBronze || !wGeneric) {
                console.error("HTML Desync: One of the modal wrappers is missing from the page!");
                return; // Stop execution before the crash happens
            }

            // Reset all wrappers to hidden safely
            wMedals.style.display = 'none';
            wGS.style.display = 'none';
            wBronze.style.display = 'none';
            wGeneric.style.display = 'none';

            let phase = data.match_phase.toLowerCase();
            let participantCount = stateListToUse.length; 
            
            // 1. Check for true Final matches
            if ((phase.includes('final') || phase.includes('akhir')) && !phase.includes('semi') && !phase.includes('separuh')) {
                
                wMedals.style.display = 'block'; 
                wGS.style.display = 'block'; 
                
                if (participantCount > 2) {
                    wBronze.style.display = 'block';
                }
            
            // 2. Check for dedicated Bronze Matches
            } else if (phase.includes('bronze') || phase.includes('gangsa')) {
                
                wMedals.style.display = 'block'; 
                wBronze.style.display = 'block'; 
            
            // 3. Fallback for all other non-medal matches
            } else {
                wGeneric.style.display = 'block';
            }

            document.getElementById('setResultModal').style.display = 'flex';
        }
    })
    .catch(error => {
        console.error("System Error when fetching event:", error);
        alert("Check your browser console (F12) - the backend sent corrupted data.");
    });
}

function closeSetResultModal() {
    document.getElementById('setResultModal').style.display = 'none';
}

function submitResult(event) {
    event.preventDefault();
    let eventId = document.getElementById('result_event_id').value;
    let gold = document.getElementById('result_gold').value;
    let silver = document.getElementById('result_silver').value;
    let bronze = document.getElementById('result_bronze').value;

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'set_result', event_id: eventId, gold: gold, silver: silver, bronze: bronze })
    })
    .then(response => response.text())
    .then(text => {
        try {
            let data = JSON.parse(text);
            if(data.status === 'success') {
                closeSetResultModal();
                location.reload(); // Instantly reload to grey-out the row!
            } else { alert('Error saving result.'); }
        } catch(e) { location.reload(); }
    });
}

// --- ATHLETE MODAL LOGIC ---
function openEditAthleteModal(id, name, state, gender) {
    document.getElementById('edit_athlete_id').value = id;
    document.getElementById('edit_athlete_name').value = name;
    document.getElementById('edit_athlete_state').value = state;
    document.getElementById('edit_athlete_gender').value = gender;
    
    document.getElementById('editAthleteModal').style.display = 'flex';
}

function closeEditAthleteModal() {
    document.getElementById('editAthleteModal').style.display = 'none';
}

// --- ATHLETE LIVE SEARCH LOGIC ---
function applyAthleteFilter() {
    let input = document.getElementById("athleteSearchInput");
    let filter = input.value.toLowerCase();
    let tableBody = document.getElementById("athlete-table-body");
    
    // Only proceed if the table actually exists on the page
    if (!tableBody) return; 

    let rows = tableBody.getElementsByTagName("tr");

    for (let i = 0; i < rows.length; i++) {
        let cols = rows[i].getElementsByTagName("td");
        
        // Ensure it's a real data row (our data rows have 4 columns)
        // This prevents the search from breaking on the "No athletes found" placeholder row
        if (cols.length > 1) { 
            // Grab all the text inside the entire row (Name + State + Gender)
            let rowText = rows[i].innerText.toLowerCase();
            
            // If the row contains the typed letters, show it. Otherwise, hide it!
            if (rowText.indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

// --- ATHLETE ASSIGNMENT LOGIC ---
function openAssignModal(athleteId, athleteName, state) {
    document.getElementById('assign_athlete_id').value = athleteId;
    document.getElementById('assign_athlete_name_display').innerText = `${athleteName} (${state})`;
    
    let dropdown = document.getElementById('assign_event_dropdown');
    dropdown.innerHTML = '<option value="">Loading available matches...</option>';
    document.getElementById('assignAthleteModal').style.display = 'flex';

    // Fetch matches that match the athlete's state!
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_state_events', state: state })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            if(data.events.length === 0) {
                dropdown.innerHTML = '<option value="" disabled selected>No upcoming matches for this state.</option>';
            } else {
                dropdown.innerHTML = '<option value="" disabled selected>-- Select a Match --</option>';
                data.events.forEach(ev => {
                    dropdown.innerHTML += `<option value="${ev.id}">${ev.event_name} (${ev.match_phase})</option>`;
                });
            }
        }
    });
}

function closeAssignModal() {
    document.getElementById('assignAthleteModal').style.display = 'none';
}

function submitAthleteAssignment(event) {
    event.preventDefault();
    let athleteId = document.getElementById('assign_athlete_id').value;
    let eventId = document.getElementById('assign_event_dropdown').value;

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'assign_athlete', athlete_id: athleteId, event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            showToast('Athlete successfully assigned to match!', 'success'); // <-- THE UPGRADE
            closeAssignModal();
        } else {
            showToast('Error: ' + data.message, 'error'); // <-- THE UPGRADE
        }
    });
}

// --- ATHLETE PROFILE CARD LOGIC ---
function openProfileModal(athleteId) {
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_athlete_profile', athlete_id: athleteId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Fill basic info
            document.getElementById('profile_name').innerText = data.athlete.full_name;
            let genderIcon = data.athlete.gender === 'Male' ? '👨' : (data.athlete.gender === 'Female' ? '👩' : '🚻');
            document.getElementById('profile_state_gender').innerText = `${genderIcon} ${data.athlete.gender}  |  📍 ${data.athlete.contingent_state}`;
            
            // Set the flag image dynamically
            let flagImg = data.athlete.contingent_state.toLowerCase().replace(/ /g, '_') + '.png';
            document.getElementById('profile_flag').src = 'assets/flags/' + flagImg;

            // Fill the events list
            let eventsList = document.getElementById('profile_events_list');
            eventsList.innerHTML = '';

            if (data.events.length === 0) {
                eventsList.innerHTML = '<div style="text-align: center; color: #777; padding: 20px; font-style: italic;">No events assigned yet.</div>';
            } else {
                data.events.forEach(ev => {
                    let statusColor = ev.match_status === 'Completed' ? '#28a745' : '#17a2b8';
                    let statusIcon = ev.match_status === 'Completed' ? '✓ Finished' : '⏳ Upcoming';
                    
                    eventsList.innerHTML += `
                        <div style="background: white; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div>
                                <strong style="color: #333; font-size: 14px;">${ev.event_name}</strong><br>
                                <span style="font-size: 12px; color: #666;">${ev.match_phase}</span>
                            </div>
                            <div style="font-size: 11px; font-weight: bold; color: ${statusColor}; background: ${statusColor}20; padding: 4px 8px; border-radius: 12px;">
                                ${statusIcon}
                            </div>
                        </div>
                    `;
                });
            }

            document.getElementById('athleteProfileModal').style.display = 'flex';
        }
    });
}

function closeProfileModal() {
    document.getElementById('athleteProfileModal').style.display = 'none';
}

// --- VIEW EVENT PARTICIPANTS & UNASSIGN LOGIC ---

// Store the current event details so the modal can refresh itself!
let currentViewEventId = null;
let currentViewEventName = '';
let currentViewEventPhase = '';

function openParticipantsModal(eventId, eventName, eventPhase) {
    currentViewEventId = eventId;
    currentViewEventName = eventName;
    currentViewEventPhase = eventPhase;
    
    document.getElementById('vp_event_title').innerText = '👥 ' + eventName;
    document.getElementById('vp_event_phase').innerText = eventPhase;
    
    let listContainer = document.getElementById('vp_participants_list');
    listContainer.innerHTML = '<tr><td style="text-align:center; padding:20px; color:#777;">Loading athletes...</td></tr>';
    document.getElementById('viewParticipantsModal').style.display = 'flex';

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_event_participants', event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            listContainer.innerHTML = '';
            
            if(data.participants.length === 0) {
                listContainer.innerHTML = '<tr><td style="text-align:center; padding:20px; color:#777; font-style:italic;">No athletes have been assigned to this match yet.</td></tr>';
            } else {
                data.participants.forEach((p) => {
                    let flagImg = p.contingent_state.toLowerCase().replace(/ /g, '_') + '.png';
                    let genderIcon = p.gender === 'Male' ? '👨' : (p.gender === 'Female' ? '👩' : '🚻');
                    
                    // NEW: We added a 3rd column with a red ✖ button!
                    listContainer.innerHTML += `
                        <tr style="border-bottom: 1px solid #ddd; transition: background 0.2s;" onmouseover="this.style.background='#fff'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 10px; width: 40px; text-align: center;">
                                <img src="assets/flags/${flagImg}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc;">
                            </td>
                            <td style="padding: 10px; font-weight: bold; font-size: 14px;">
                                <a href="#" onclick="closeParticipantsModal(); openProfileModal(${p.id}); return false;" style="display: inline-flex; align-items: center; gap: 8px; color: #444; text-decoration: none; padding: 4px 8px; border-radius: 4px; background: #f8f9fa; border: 1px solid #ddd; transition: all 0.2s;" onmouseover="this.style.background='#0056b3'; this.style.color='white'; this.style.borderColor='#0056b3';" onmouseout="this.style.background='#f8f9fa'; this.style.color='#444'; this.style.borderColor='#ddd';">
                                    🪪 ${p.full_name}
                                </a>
                            </td>
                            <td style="padding: 10px; text-align: center; font-size: 12px; color: #666;">
                                ${genderIcon} ${p.contingent_state}
                            </td>
                            <td style="padding: 10px; text-align: right; width: 40px;">
                                <button onclick="unassignAthlete(${p.id}, '${p.full_name.replace(/'/g, "\\'")}')" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.3)'" onmouseout="this.style.transform='scale(1)'" title="Remove Athlete from Match">✖</button>
                            </td>
                        </tr>
                    `;
                });
            }
        }
    });
}

function closeParticipantsModal() {
    document.getElementById('viewParticipantsModal').style.display = 'none';
}

// Temporary storage for the removal process
let athleteToUnassign = null;
let athleteNameToUnassign = "";

// 1. Triggered when clicking the ✖ button
function unassignAthlete(athleteId, athleteName) {
    athleteToUnassign = athleteId;
    athleteNameToUnassign = athleteName;
    
    // Set the text in the modal and show it
    document.getElementById('unassign_confirm_text').innerText = `Are you sure you want to remove ${athleteName} from this match?`;
    document.getElementById('confirmUnassignModal').style.display = 'flex';
}

function closeUnassignConfirm() {
    document.getElementById('confirmUnassignModal').style.display = 'none';
    athleteToUnassign = null;
}

// 2. Triggered when clicking "Yes, Remove Athlete" inside the modal
document.getElementById('executeUnassignBtn').onclick = function() {
    if(!athleteToUnassign || !currentViewEventId) return;
    
    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            action: 'remove_athlete', 
            athlete_id: athleteToUnassign, 
            event_id: currentViewEventId 
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Close confirmation modal
            closeUnassignConfirm();
            
            // Show the bouncy red toast notification we built
            showToast(`${athleteNameToUnassign} removed from match.`, 'error');
            
            // Refresh the participant list instantly
            openParticipantsModal(currentViewEventId, currentViewEventName, currentViewEventPhase);
        } else {
            showToast('Error removing athlete.', 'error');
        }
    });
};

// --- MEDAL WINNERS DRILL-DOWN LOGIC ---
function openMedalWinnersModal(state, medalType) {
    let titleStr = "🏆 Medal Winners";
    let colorHex = "#333";
    
    if (medalType === 'gold') { titleStr = "🥇 Gold Medalists"; colorHex = "#d4af37"; }
    if (medalType === 'silver') { titleStr = "🥈 Silver Medalists"; colorHex = "#9e9e9e"; }
    if (medalType === 'bronze') { titleStr = "🥉 Bronze Medalists"; colorHex = "#cd7f32"; }

    document.getElementById('mw_title').innerText = titleStr;
    document.getElementById('mw_title').style.color = colorHex;
    document.getElementById('mw_subtitle').innerText = "Contingent: " + state.toUpperCase();
    
    let listContainer = document.getElementById('mw_winners_list');
    listContainer.innerHTML = '<tr><td style="text-align:center; padding:20px; color:#777;">Fetching records...</td></tr>';
    document.getElementById('medalWinnersModal').style.display = 'flex';

    fetch('event_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_medal_winners', state: state, medal_type: medalType })
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            listContainer.innerHTML = '';
            
            if(data.winners.length === 0) {
                listContainer.innerHTML = '<tr><td style="text-align:center; padding:20px; color:#dc3545; font-style:italic;">⚠️ Medals awarded, but no specific athletes have been assigned to this event yet!</td></tr>';
            } else {
                data.winners.forEach((w) => {
                    let medalIcon = w.medal_won === 'Gold' ? '🥇' : (w.medal_won === 'Silver' ? '🥈' : '🥉');
                    let medalBadgeColor = w.medal_won === 'Gold' ? '#fffdf5' : (w.medal_won === 'Silver' ? '#f8f9fa' : '#fdf6f2');
                    let medalBorderColor = w.medal_won === 'Gold' ? '#ffecb3' : (w.medal_won === 'Silver' ? '#ddd' : '#f5d0b5');
                    
                    listContainer.innerHTML += `
                        <tr style="border-bottom: 1px solid #eee; background: ${medalBadgeColor}; transition: filter 0.2s;" onmouseover="this.style.filter='brightness(0.95)'" onmouseout="this.style.filter='brightness(1)'">
                            <td style="padding: 12px; font-weight: bold; font-size: 14px; border-left: 4px solid ${medalBorderColor};">
                                <a href="#" onclick="closeMedalWinnersModal(); openProfileModal(${w.id}); return false;" style="color: #0056b3; text-decoration: none; border-bottom: 1px dashed transparent; transition: all 0.2s;" onmouseover="this.style.color='#da251d'; this.style.borderBottom='1px dashed #da251d';" onmouseout="this.style.color='#0056b3'; this.style.borderBottom='1px dashed transparent';">
                                    ${w.full_name}
                                </a>
                            </td>
                            <td style="padding: 12px; text-align: right; font-size: 12px; color: #555;">
                                ${medalIcon} ${w.event_name}
                            </td>
                        </tr>
                    `;
                });
            }
        }
    });
}

function closeMedalWinnersModal() {
    document.getElementById('medalWinnersModal').style.display = 'none';
}

// --- CUSTOM TOAST NOTIFICATION ENGINE ---
let toastTimeout;
function showToast(message, type = 'success') {
    let toast = document.getElementById('systemToast');
    let msg = document.getElementById('toastMessage');
    let icon = document.getElementById('toastIcon');

    msg.innerText = message;

    // Theme the toast based on what happened
    if (type === 'success') {
        toast.style.background = '#28a745'; // Green
        icon.innerText = '✅';
    } else if (type === 'error') {
        toast.style.background = '#dc3545'; // Red
        icon.innerText = '⚠️';
    } else {
        toast.style.background = '#0056b3'; // Blue
        icon.innerText = 'ℹ️';
    }

    // Slide it in
    toast.style.right = '20px'; 

    // Clear any existing timers so they don't overlap, then hide after 3 seconds
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.style.right = '-400px'; 
    }, 3000);
}

</script>

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