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
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                
                <!-- CARD: MATCH PHASES -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #da251d; border-bottom: 2px solid #da251d; padding-bottom: 5px; display: inline-block;">Match Phases</h4>
                    
                    <!-- Add Form -->
                    <form method="POST" action="dashboard.php" style="display: flex; gap: 10px; margin-bottom: 15px; margin-top: 15px;">
                        <input type="hidden" name="setting_action" value="add_phase">
                        <input type="text" name="phase_name" placeholder="e.g. Semi-Finals" required style="flex: 1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <button type="submit" style="background: #da251d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add</button>
                    </form>

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody>
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

                    <!-- Data Table -->
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <table style="width: 100%; border-collapse: collapse; background: white; font-size: 14px;">
                            <tbody>
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
                
                <!-- Future Cards (Venues, Sports) will go here later -->

            </div>
        </div>
    </div>
</div>
<?php endif; ?>