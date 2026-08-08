<?php
// --- CUSTOM STATE ACRONYM ENGINE ---
// This prevents conflicts between states like Perak/Perlis
function getStateAcronym($state_name) {
    if (empty($state_name)) return '';
    
    $state = strtoupper(trim($state_name));
    
    // Define custom overrides here!
    $overrides = [
        'PERAK' => 'PRK',
        'PERLIS' => 'PER',
        'PULAU PINANG' => 'PEN',
        'NEGERI SEMBILAN' => 'NSE',
        'WILAYAH PERSEKUTUAN' => 'WP',
        'KUALA LUMPUR' => 'KUL',
        'SABAH' => 'SBH',
        'SARAWAK' => 'SWK',
        'JOHOR' => 'JHR',
        'KEDAH' => 'KDH',
        'TERENGGANU' => 'TRG'
    ];

    // If it's in the override list, use the custom one. Otherwise, use the first 3 letters.
    return isset($overrides[$state]) ? $overrides[$state] : substr($state, 0, 3);
}
?>

<div id="Events" class="tab-content">
    <div class="generic-container" style="max-width: 98%; width: 98%; min-height: 450px; overflow: visible !important; padding-bottom: 50px;">
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

        <table style="font-size: 14px;">
            <thead>
                <tr>
                    <th class="center" style="width: 100px;">Time</th>
                    <th>Sport</th>
                    <th>Category</th>
                    <th>Phase</th>
                    <th class="center">Contender / Athletes</th>
                    <th>Venue</th>
                    
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                        <th class="center" style="width: 120px;">Action</th>
                    <?php else: ?>
                        <th class="center" style="width: 120px;">Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="events-table-body">
                <?php 
                    mysqli_data_seek($events_result, 0);

                    $current_group_key = '';
                    $has_shown_divider = false; 
                    

                    while($event = mysqli_fetch_assoc($events_result)) { 
                        
                        $row_date_formatted = date('d M Y (l)', strtotime($event['event_date']));

                        $status_group = ($event['match_status'] == 'Completed') ? 'Completed' : 'Upcoming';
                        $group_key = $status_group . '_' . $row_date_formatted;

                        if ($status_group == 'Completed' && !$has_shown_divider) {
                            echo "<tr class='completed-divider' style='background: #e9ecef; border-top: 2px solid #ccc; border-bottom: 2px solid #ccc;'>
                                    <td colspan='7' style='text-align: center; font-weight: bold; color: #6c757d; padding: 10px; letter-spacing: 2px; font-size: 12px;'>
                                        ⬇️ COMPLETED MATCHES ⬇️
                                    </td>
                                  </tr>";
                            $has_shown_divider = true;
                        }

                        if ($current_group_key !== $group_key) {
                            
                            $banner_bg = ($status_group == 'Completed') ? '#f8f9fa' : '#ffecb3';
                            $banner_border = ($status_group == 'Completed') ? '#ddd' : '#ffe082';
                            $banner_text = ($status_group == 'Completed') ? '#6c757d' : '#856404';

                            echo "<tr class='date-header-row' style='background: {$banner_bg}; border-top: 2px solid {$banner_border}; border-bottom: 2px solid {$banner_border};'>
                                    <td colspan='7' style='font-weight: bold; color: {$banner_text}; padding: 10px 15px; letter-spacing: 1px;'>
                                        📅 " . strtoupper($row_date_formatted) . "
                                    </td>
                                  </tr>";
                            
                            $current_group_key = $group_key; // Update the memory tracker
                        }
        
                        // --- BUILD THE PARTICIPANT FLAGS (Venue removed from here) ---
                        $participants_html = "<span style='color:#aaa;'>TBD</span>";
        
                        if (!empty($event['participating_states'])) {
                            $states_array = json_decode($event['participating_states'], true);
                            if (is_array($states_array) && count($states_array) > 0) {
                                $participants_html = "<div style='display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: center;'>";

                                if (count($states_array) == 2) {
                                    $s1 = $states_array[0]; $img1 = strtolower(str_replace(' ', '_', $s1)) . '.png';
                                    $s2 = $states_array[1]; $img2 = strtolower(str_replace(' ', '_', $s2)) . '.png';

                                    $participants_html .= "
                                        <div style='text-align: center;'>
                                            <img src='assets/flags/{$img1}' style='width:35px; height:35px; border-radius:50%; object-fit:cover; border:1px solid #ddd;' title='{$s1}'><br>
                                            <span style='font-size: 11px; font-weight: bold; color: #da251d;'>{$s1}</span>
                                        </div>
                                        <span style='font-size:12px; font-weight:bold; color:#777;'>VS</span>
                                        <div style='text-align: center;'>
                                            <img src='assets/flags/{$img2}' style='width:35px; height:35px; border-radius:50%; object-fit:cover; border:1px solid #ddd;' title='{$s2}'><br>
                                            <span style='font-size: 11px; font-weight: bold; color: #0056b3;'>{$s2}</span>
                                        </div>";
                                } else {
                                    foreach($states_array as $s) {
                                        $img = strtolower(str_replace(' ', '_', $s)) . '.png';
                                        $participants_html .= "
                                        <div style='text-align: center; width: 45px;'>
                                            <img src='assets/flags/{$img}' style='width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid #ddd;' title='{$s}'><br>
                                            <span style='font-size: 9px; color: #000000; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;'>{$s}</span>
                                        </div>";
                                    }
                                }
                                $participants_html .= "</div>";
                            }
                        }

                        // --- THE COMPLETED DIVIDER ROW (Colspan 7) ---
                        if ($event['match_status'] == 'Completed' && !$has_shown_divider) {
                            echo "<tr class='completed-divider' style='background: #e9ecef; border-top: 2px solid #ccc; border-bottom: 2px solid #ccc;'>
                                    <td colspan='7' style='text-align: center; font-weight: bold; color: #6c757d; padding: 10px; letter-spacing: 2px; font-size: 12px;'>
                                        ⬇️ COMPLETED MATCHES ⬇️
                                    </td>
                                  </tr>";
                            $has_shown_divider = true;
                        }
 
                        $bg_color = ($event['match_status'] == 'Completed') ? '#fcfcfc' : '#ffffff';
                ?>
    
                <tr id="event-row-<?php echo $event['id']; ?>" class="schedule-row" data-status="<?php echo $event['match_status']; ?>" style="opacity: <?php echo $opacity; ?>; background-color: <?php echo $bg_color; ?>;">
        
                    <!-- COLUMN 1: TIME -->
                    <td id="td-date-<?php echo $event['id']; ?>" class="center" data-raw-date="<?php echo $event['event_date']; ?>">
                        <?php if(!empty($event['event_time'])): ?>
                            <div style="font-weight: bold; color: #333; font-size: 14px;"><?php echo date('h:i A', strtotime($event['event_time'])); ?></div>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">TBD</span>
                        <?php endif; ?>
                    </td>

                    <!-- COLUMN 2: SPORT -->
                    <td style="font-weight: bold; color: #222;">
                        <?php echo htmlspecialchars($event['event_name']); ?>
                    </td>

                    <!-- COLUMN 3: CATEGORY / DISCIPLINE -->
                    <td style="color: #da251d; font-weight: 500;">
                        <?php echo !empty($event['event_discipline']) ? htmlspecialchars($event['event_discipline']) : '<span style="color:#ccc;">-</span>'; ?>
                    </td>

                    <!-- COLUMN 4: PHASE -->
                    <td data-match-phase="<?php echo htmlspecialchars($event['match_phase']); ?>" style="color: #555;">
                        <?php echo htmlspecialchars($event['match_phase']); ?>
                    </td>
        
                    <!-- COLUMN 5: CONTENDERS -->
                    <td id="td-participants-<?php echo $event['id']; ?>" style="line-height: 1.4; padding-top: 15px; padding-bottom: 15px;">
                        <?php echo $participants_html; ?>
                    </td>
        
                    <!-- COLUMN 6: VENUE -->
                    <td style="color: #444; font-size: 13px;">
                        📍 <?php echo htmlspecialchars($event['venue']); ?>
                    </td>

                    <!-- COLUMN 7: ACTION / STATUS -->
                    <td class="center" style="white-space: nowrap;">
                        
                        <?php if($event['match_status'] == 'Completed'): ?>
                            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                                <button onclick="openParticipantsModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['event_name']); ?>', '<?php echo addslashes($event['match_phase']); ?>')" style="background: none; border: none; cursor: pointer; color: #0056b3; font-size: 16px; margin-right: 4px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="View Athletes">👥</button>

                                <button onclick="openSetResultModal(<?php echo $event['id']; ?>)" style="background: #fff; border: 1px solid #ddd; padding: 5px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" title="Edit Results">
                                    <?php 
                                        if($event['gold_winner']) echo "<span style='color: #d4af37;'>🥇" . getStateAcronym($event['gold_winner']) . "</span>";
                                        if($event['silver_winner']) echo "<span style='color: #9e9e9e; margin-left: 4px;'>🥈" . getStateAcronym($event['silver_winner']) . "</span>";
                                        if($event['bronze_winner']) echo "<span style='color: #cd7f32; margin-left: 4px;'>🥉" . getStateAcronym($event['bronze_winner']) . "</span>";
                                        
                                        // Upgraded Winner Badges
                                        if(!$event['gold_winner'] && !$event['silver_winner']) {
                                            if(!empty($event['match_winner'])) {
                                                echo "<span style='background: #e6f4ea; color: #1e8e3e; border: 1px solid #ceead6; padding: 4px 10px; border-radius: 12px; font-weight: 900; font-size: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); letter-spacing: 0.5px;'>⭐ " . getStateAcronym($event['match_winner']) . " WON</span>";
                                            } else {
                                                echo "<span style='background: #f1f3f4; color: #5f6368; border: 1px solid #dadce0; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 12px;'>✓ FINISHED</span>";
                                            }
                                        }
                                    ?>
                                </button>

                                <!-- NEW: Delete Button for Completed Matches -->
                                <button onclick="openDeleteEventModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 18px; margin-left: 8px; vertical-align: middle; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Delete Match Data">🗑️</button>
                            
                            <?php else: ?>
                                <div style="background: #fff; border: 1px solid #ddd; padding: 5px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block;">
                                    <?php 
                                        if($event['gold_winner']) echo "<span style='color: #d4af37;'>🥇" . getStateAcronym($event['gold_winner']) . "</span>";
                                        if($event['silver_winner']) echo "<span style='color: #9e9e9e; margin-left: 4px;'>🥈" . getStateAcronym($event['silver_winner']) . "</span>";
                                        if($event['bronze_winner']) echo "<span style='color: #cd7f32; margin-left: 4px;'>🥉" . getStateAcronym($event['bronze_winner']) . "</span>";
                                        
                                        // Upgraded Winner Badges
                                        if(!$event['gold_winner'] && !$event['silver_winner']) {
                                            if(!empty($event['match_winner'])) {
                                                echo "<span style='background: #e6f4ea; color: #1e8e3e; border: 1px solid #ceead6; padding: 4px 10px; border-radius: 12px; font-weight: 900; font-size: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); letter-spacing: 0.5px;'>⭐ " . getStateAcronym($event['match_winner']) . " WON</span>";
                                            } else {
                                                echo "<span style='background: #f1f3f4; color: #5f6368; border: 1px solid #dadce0; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 12px;'>✓ FINISHED</span>";
                                            }
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
                                <button onclick="openParticipantsModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['event_name']); ?>', '<?php echo addslashes($event['match_phase']); ?>')" style="background: none; border: none; cursor: pointer; color: #0056b3; font-size: 18px; margin-right: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="View Athletes">👥</button>
                                <button onclick="openSetResultModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #d4af37; font-size: 20px; margin-right: 8px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Set Result">🏆</button>
                                <button onclick="openEditEventModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #17a2b8; font-size: 18px; margin-right: 4px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Edit">✏️</button>
                                <button onclick="openDeleteEventModal(<?php echo $event['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Delete">🗑️</button>
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