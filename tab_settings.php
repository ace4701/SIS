<?php if($_SESSION['role'] == 'admin'): ?>
<div id="Settings" class="tab-content">
    <div class="dashboard-wrapper">
        <div class="generic-container" style="width: 100%;">
            <h3 style="margin-top: 0; color: #333;">⚙️ Master Data Management</h3>
            <p style="color: #666; margin-bottom: 20px;">Manage system-wide dropdown lists and categories.</p>

            <?php
            // Display success/error messages for settings actions
            if (isset($_SESSION['setting_msg'])) {
                echo $_SESSION['setting_msg'];
                unset($_SESSION['setting_msg']);
            }
            ?>

            <!-- Settings Grid Container -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
                
                <!-- CARD: MATCH PHASES -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #da251d; border-bottom: 2px solid #da251d; padding-bottom: 5px; display: inline-block;">Match Phases</h4>
                    
                    <!-- Add Form -->
                    <form method="POST" action="dashboard.php" style="display: flex; gap: 10px; margin-bottom: 15px; margin-top: 15px;">
                        <input type="hidden" name="setting_action" value="add_phase">
                        <input type="text" name="phase_name" placeholder="e.g. Semi-Finals" required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <button type="submit" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add</button>
                    </form>

                    <!-- Search Bar -->
                    <input type="text" id="search_phases" onkeyup="filterMDM('search_phases', 'tbody_phases')" placeholder="🔍 Search match phases..." style="width: 100%; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; background: #fff;">

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody id = "tbody_phases">
                                <?php
                                // Notice we changed the ORDER BY to phase_order ASC
                                $phases_query = mysqli_query($conn, "SELECT id, phase_name, phase_order FROM match_phases ORDER BY phase_order ASC");
                                while ($phase = mysqli_fetch_assoc($phases_query)) {
                                    echo "<tr style='border-bottom: 1px solid #eee;'>";
                                    
                                    // Display the Phase Name
                                    echo "<td style='padding: 10px;'>" . htmlspecialchars($phase['phase_name']) . "</td>";
                                    
                                    // Button Container
                                    echo "<td style='padding: 10px; text-align: right; width: 140px;'>";
                                    echo "<div style='display: flex; gap: 4px; justify-content: flex-end;'>";

                                    // Move Up Button
                                    echo "<form method='POST' action='dashboard.php' style='margin:0;'>";
                                    echo "<input type='hidden' name='setting_action' value='move_phase'>";
                                    echo "<input type='hidden' name='phase_id' value='" . $phase['id'] . "'>";
                                    echo "<input type='hidden' name='direction' value='up'>";
                                    echo "<button type='submit' style='background: #e2e8f0; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px;' title='Move Up'>⬆</button>";
                                    echo "</form>";

                                    // Move Down Button
                                    echo "<form method='POST' action='dashboard.php' style='margin:0;'>";
                                    echo "<input type='hidden' name='setting_action' value='move_phase'>";
                                    echo "<input type='hidden' name='phase_id' value='" . $phase['id'] . "'>";
                                    echo "<input type='hidden' name='direction' value='down'>";
                                    echo "<button type='submit' style='background: #e2e8f0; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px;' title='Move Down'>⬇</button>";
                                    echo "</form>";

                                    // Delete Button
                                    echo "<form method='POST' action='dashboard.php' onsubmit='return confirm(\"Delete this match phase?\");' style='margin:0; margin-left: 5px;'>";
                                    echo "<input type='hidden' name='setting_action' value='delete_phase'>";
                                    echo "<input type='hidden' name='phase_id' value='" . $phase['id'] . "'>";
                                    echo "<button type='submit' style='background: transparent; color: #dc3545; border: none; cursor: pointer; font-size: 16px; padding: 2px 5px;' title='Delete'>🗑️</button>";
                                    echo "</form>";

                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CARD: VENUES -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #da251d; border-bottom: 2px solid #da251d; padding-bottom: 5px; display: inline-block;">Venues</h4>
                    
                    <!-- Add Form -->
                    <form method="POST" action="dashboard.php" style="display: flex; gap: 10px; margin-bottom: 15px; margin-top: 15px;">
                        <input type="hidden" name="setting_action" value="add_venue">
                        <input type="text" name="venue_name" placeholder="e.g. STADIUM BUKIT JALIL" required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <button type="submit" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add</button>
                    </form>

                    <!-- Search Bar -->
                    <input type="text" id="search_venues" onkeyup="filterMDM('search_venues', 'tbody_venues')" placeholder="🔍 Search venues..." style="width: 100%; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; background: #fff;">

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody id="tbody_venues">
                                <?php
                                // Order alphabetically so the dropdowns look neat
                                $venues_query = mysqli_query($conn, "SELECT id, venue_name FROM venues_list ORDER BY venue_name ASC");
                                while ($venue = mysqli_fetch_assoc($venues_query)) {
                                    echo "<tr style='border-bottom: 1px solid #eee;'>";
                                    echo "<td style='padding: 10px;'>" . htmlspecialchars($venue['venue_name']) . "</td>";
                                    echo "<td style='padding: 10px; text-align: right; width: 40px;'>";
                                    echo "<form method='POST' action='dashboard.php' onsubmit='return confirm(\"Delete this venue?\");' style='margin:0;'>";
                                    echo "<input type='hidden' name='setting_action' value='delete_venue'>";
                                    echo "<input type='hidden' name='venue_id' value='" . $venue['id'] . "'>";
                                    echo "<button type='submit' style='background: transparent; color: #dc3545; border: none; cursor: pointer; font-size: 16px; padding: 2px 5px;' title='Delete'>🗑️</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CARD: SPORTS CATEGORIES -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #da251d; border-bottom: 2px solid #da251d; padding-bottom: 5px; display: inline-block;">Sports Categories</h4>
                    
                    <!-- Add Form -->
                    <form method="POST" action="dashboard.php" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; margin-top: 15px;">
                        <input type="hidden" name="setting_action" value="add_sport">
                        
                        <!-- Top Row: Sport Name Input -->
                        <input type="text" name="sport_name" placeholder="e.g. Ping Pong" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                        
                        <!-- Bottom Row: Dropdown and Button -->
                        <div style="display: flex; gap: 10px;">
                            <select name="format_type" required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: white;">
                                <option value="h2h">1 vs 1 (H2H)</option>
                                <option value="group">Group / Heat</option>
                            </select>
                            
                            <button type="submit" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add</button>
                        </div>
                    </form>

                    <!-- Search Bar -->
                    <input type="text" id="search_sports" onkeyup="filterMDM('search_sports', 'tbody_sports')" placeholder="🔍 Search sports..." style="width: 100%; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; background: #fff;">

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody id="tbody_sports">
                                <?php
                                $sports_query = mysqli_query($conn, "SELECT id, sport_name, format_type FROM sports_list ORDER BY sport_name ASC");
                                while ($sport = mysqli_fetch_assoc($sports_query)) {
                                    echo "<tr style='border-bottom: 1px solid #eee;'>";
                                    echo "<td style='padding: 10px'>" . htmlspecialchars($sport['sport_name']) . "</td>";
                                    
                                    // Display the format type as a tiny badge
                                    echo "<td style='padding: 10px; color: #666; font-size: 12px;'>" . strtoupper($sport['format_type']) . "</td>";
                                    
                                    echo "<td style='padding: 10px; text-align: right; width: 40px;'>";
                                    // Heavy warning on deletion to prevent orphaned database records!
                                    echo "<form method='POST' action='dashboard.php' onsubmit='return confirm(\"WARNING: If you delete a sport that already has scheduled matches, those matches will break! Are you sure?\");' style='margin:0;'>";
                                    echo "<input type='hidden' name='setting_action' value='delete_sport'>";
                                    echo "<input type='hidden' name='sport_id' value='" . $sport['id'] . "'>";
                                    echo "<button type='submit' style='background: transparent; color: #dc3545; border: none; cursor: pointer; font-size: 16px; padding: 2px 5px;' title='Delete'>🗑️</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- CARD: DISCIPLINES -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #da251d; border-bottom: 2px solid #da251d; padding-bottom: 5px; display: inline-block;">Disciplines / Categories</h4>
                    
                    <!-- Add Form -->
                    <form method="POST" action="dashboard.php" style="display: flex; gap: 10px; margin-bottom: 15px; margin-top: 15px;">
                        <input type="hidden" name="setting_action" value="add_discipline">
                        <input type="text" name="discipline_name" placeholder="e.g. Men's Pairs" required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <button type="submit" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add</button>
                    </form>

                    <!-- Search Bar -->
                    <input type="text" id="search_disciplines" onkeyup="filterMDM('search_disciplines', 'tbody_disciplines')" placeholder="🔍 Search disciplines..." style="width: 100%; padding: 6px 10px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 13px; background: #fff;">

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody id="tbody_disciplines">
                                <?php
                                $disc_query = mysqli_query($conn, "SELECT id, discipline_name FROM disciplines_list ORDER BY discipline_name ASC");
                                while ($disc = mysqli_fetch_assoc($disc_query)) {
                                    echo "<tr style='border-bottom: 1px solid #eee;'>";
                                    echo "<td style='padding: 10px;'>" . htmlspecialchars($disc['discipline_name']) . "</td>";
                                    echo "<td style='padding: 10px; text-align: right; width: 40px;'>";
                                    echo "<form method='POST' action='dashboard.php' onsubmit='return confirm(\"Delete this discipline?\");' style='margin:0;'>";
                                    echo "<input type='hidden' name='setting_action' value='delete_discipline'>";
                                    echo "<input type='hidden' name='discipline_id' value='" . $disc['id'] . "'>";
                                    echo "<button type='submit' style='background: transparent; color: #dc3545; border: none; cursor: pointer; font-size: 16px; padding: 2px 5px;' title='Delete'>🗑️</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Future Cards (Venues, Sports) will go here later -->

            </div>
        </div>
    </div>

<!-- MDM Live Search Engine -->
<script>
function filterMDM(inputId, tbodyId) {
    let input = document.getElementById(inputId).value.toLowerCase();
    let rows = document.getElementById(tbodyId).getElementsByTagName("tr");
    
    for (let row of rows) {
        // We only search the first column (the name of the item)
        let text = row.cells[0].textContent.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
    }
}
</script>

</div>
<?php endif; ?>