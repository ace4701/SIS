<?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'staff'): ?>
    
<div id="Athletes" class="tab-content">
    <div class="dashboard-wrapper">
        
        <!-- LEFT PANEL: ADD ATHLETE FORM -->
        <div class="side-panel" style="flex: 1; min-width: 300px;">
            <div class="generic-container" style="position: sticky; top: 20px;">
                <h3 style="color: #da251d; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 0;">Register New Athlete</h3>
                
                <?php echo $athlete_msg; ?>
                
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="add_athlete">
                    
                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 15px;">Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g., AHMAD BIN ABU" style="width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    
                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 10px;">Contingent / State</label>
                    <select name="contingent_state" required style="width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="" disabled selected>-- Select State --</option>
                        <?php 
                        // Using your existing $all_states array!
                        foreach(array_keys($all_states) as $s): ?>
                            <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label style="font-weight: bold; font-size: 14px; display: block; margin-top: 10px;">Gender Category</label>
                    <select name="gender" required style="width: 100%; padding: 10px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                        <option value="" disabled selected>-- Select Gender --</option>
                        <option value="Male">Male (Lelaki)</option>
                        <option value="Female">Female (Wanita)</option>
                        <option value="Mixed">Mixed (Campuran)</option>
                    </select>
                    
                    <button type="submit" style="width: 100%; background: #28a745; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 15px;">💾 Register Athlete</button>
                </form>

                <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0 15px 0;">
                <h4 style="color: #0056b3; margin-top: 0; text-align: center;">Bulk Import (CSV)</h4>
                <form method="POST" action="dashboard.php" enctype="multipart/form-data" style="text-align: center;">
                    <input type="hidden" name="action" value="import_csv">
                    <input type="file" name="csv_file" accept=".csv" required style="margin-bottom: 10px; font-size: 12px; width: 100%;">
                    <button type="submit" style="width: 100%; background: #6c757d; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">📁 Upload CSV</button>
                    <p style="font-size: 11px; color: #777; margin-top: 5px;">Format: Name, State, Gender</p>
                </form>

            </div>
        </div>

        <!-- RIGHT PANEL: ATHLETE MASTER LIST -->
        <div class="center-panel" style="flex: 2;">
            <div class="generic-container" style="padding: 0; overflow: hidden;">
                
                <div style="padding: 20px; background: white; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0;">Athlete Master List (<?php echo mysqli_num_rows($athletes_query); ?>)</h3>
                    
                    <div style="margin: 0; display: flex; gap: 10px;">
                        <input type="text" id="athleteSearchInput" onkeyup="applyAthleteFilter()" placeholder="🔍 Live search Name, State, or Gender..." style="padding: 8px 15px; border: 1px solid #ccc; border-radius: 20px; outline: none; font-size: 14px; width: 300px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);">
                    </div>
                </div>

                <div style="max-height: 600px; overflow-y: auto;">
                    <table style="margin: 0; width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <tr>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;">Athlete Name</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">State</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">Gender</th>
                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="athlete-table-body">
                            <?php 
                            if(mysqli_num_rows($athletes_query) > 0) {
                                while($row = mysqli_fetch_assoc($athletes_query)) { 
                                    $img = strtolower(str_replace(' ', '_', $row['contingent_state'])) . '.png';
                            ?>
                                <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;" onmouseover="this.style.background='#fcfcfc'" onmouseout="this.style.background='transparent'">
                                    
                                    <td style="padding: 12px; font-weight: bold; font-size: 14px;">
                                        <a href="#" onclick="openProfileModal(<?php echo $row['id']; ?>); return false;" style="display: inline-flex; align-items: center; gap: 8px; color: #444; text-decoration: none; padding: 6px 12px; border-radius: 4px; background: #f8f9fa; border: 1px solid #ddd; transition: all 0.2s; box-shadow: inset 0 -2px 0 rgba(0,0,0,0.05);" onmouseover="this.style.background='#0056b3'; this.style.color='white'; this.style.borderColor='#0056b3';" onmouseout="this.style.background='#f8f9fa'; this.style.color='#444'; this.style.borderColor='#ddd';">
                                            <span style="font-size: 16px;">🪪</span> 
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </a>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                            <img src="assets/flags/<?php echo $img; ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;" alt="flag">
                                            <span style="font-size: 13px; font-weight: bold; color: #555;"><?php echo strtoupper(substr($row['contingent_state'], 0, 3)); ?></span>
                                        </div>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">
                                        <?php 
                                        if($row['gender'] == 'Male') echo "👨 Lelaki";
                                        if($row['gender'] == 'Female') echo "👩 Wanita";
                                        if($row['gender'] == 'Mixed') echo "🚻 Campuran";
                                        ?>
                                    </td>
                                    
                                    <td style="padding: 12px; text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 10px; align-items: center;">

                                            <button type="button" onclick="openAssignModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['full_name']); ?>', '<?php echo addslashes($row['contingent_state']); ?>')" style="background: none; border: none; color: #28a745; cursor: pointer; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Assign to Match">📅</button>                                        
                                            <button type="button" onclick="openEditAthleteModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['full_name']); ?>', '<?php echo addslashes($row['contingent_state']); ?>', '<?php echo addslashes($row['gender']); ?>')" style="background: none; border: none; color: #17a2b8; cursor: pointer; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Edit Athlete">✏️</button>
                                                <form method="POST" action="dashboard.php" onsubmit="return confirm('Delete this athlete?');" style="margin: 0;">
                                                    <input type="hidden" name="action" value="delete_athlete">
                                                    <input type="hidden" name="athlete_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 18px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'" title="Delete Athlete">🗑️</button>
                                                </form>
        
                                        </div>
                                    </td>
                                </tr>
                            <?php } } else { ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px; color: #777;">No athletes found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>