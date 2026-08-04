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