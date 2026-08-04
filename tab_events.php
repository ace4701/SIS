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