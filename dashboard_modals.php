<div id="customDeleteModal" class="custom-modal">
    <div class="modal-box">
        <h3 class="modal-title">Delete Announcement?</h3>
        <p class="modal-text">This action cannot be undone. All attached images, likes, and comments will be permanently erased from the system.</p>
        <div class="modal-actions">
            <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
            <button id="confirmDeleteBtn" class="btn-danger">Yes, Delete Post</button>
        </div>
    </div>
</div>

<div id="addEventModal" class="custom-modal">
    <div class="modal-box" style="width: 450px; text-align: left;">
        <h3 class="modal-title" style="margin-bottom: 20px; color: #0056b3; text-align: center;">Schedule New Event</h3>
        
        <form id="addEventForm" onsubmit="submitNewEvent(event)">
            
            <label style="font-weight: bold; font-size: 14px;">Sport / Event Name</label>
            <select id="modal_event_name" onchange="renderStateInputs()" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" data-format="" disabled selected>-- Select an Official Sport --</option>
                <?php 
                mysqli_data_seek($sports_query, 0);
                while($sport = mysqli_fetch_assoc($sports_query)) {
                    // We hide the format inside a 'data-format' attribute!
                    echo "<option value='" . htmlspecialchars($sport['sport_name']) . "' data-format='" . $sport['format_type'] . "'>" . $sport['sport_name'] . "</option>";
                }
                ?>
            </select>
            
            <div id="dynamic-state-inputs" style="margin-bottom: 15px; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #eee; display: none;">
                </div>
            <label style="font-weight: bold; font-size: 14px;">Venue / Stadium</label>
            <select id="modal_venue" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" disabled selected>-- Select an Official Venue --</option>
                <?php 
                mysqli_data_seek($venues_query, 0);
                while($venue = mysqli_fetch_assoc($venues_query)) {
                    echo "<option value='" . htmlspecialchars($venue['venue_name']) . "'>" . $venue['venue_name'] . "</option>";
                }
                ?>
            </select>
            
            <label style="font-weight: bold; font-size: 14px;">Match Phase / Level</label>
            <select id="modal_match_phase" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" disabled selected>-- Select Match Phase --</option>
                <?php 
                mysqli_data_seek($phases_query, 0);
                while($phase = mysqli_fetch_assoc($phases_query)) {
                    echo "<option value='" . htmlspecialchars($phase['phase_name']) . "'>" . $phase['phase_name'] . "</option>";
                }
                ?>
            </select>
            <label style="font-weight: bold; font-size: 14px;">Event Date</label>
            <input type="date" id="modal_event_date" required style="width: 100%; padding: 10px; margin: 8px 0 25px 0; border: 1px solid #ccc; border-radius: 4px;">
            
            <div class="modal-actions">
                <button type="button" onclick="closeAddEventModal()" class="btn-cancel">Cancel</button>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Save Event</button>
            </div>
        </form>
    </div>
</div>

<div id="customDeleteEventModal" class="custom-modal">
    <div class="modal-box">
        <h3 class="modal-title">Delete Event?</h3>
        <p class="modal-text">Are you sure you want to remove this event from the schedule? This action cannot be undone.</p>
        <div class="modal-actions">
            <button onclick="closeDeleteEventModal()" class="btn-cancel">Cancel</button>
            <button id="confirmDeleteEventBtn" class="btn-danger">Yes, Delete Event</button>
        </div>
    </div>
</div>

<div id="editEventModal" class="custom-modal">
    <div class="modal-box" style="width: 450px; text-align: left;">
        <h3 class="modal-title" style="margin-bottom: 20px; color: #0056b3; text-align: center;">Edit Event Schedule</h3>
        
        <form id="editEventForm" onsubmit="submitEditEvent(event)">
            <input type="hidden" id="edit_event_id">

            <label style="font-weight: bold; font-size: 14px;">Sport / Event Name</label>
            <select id="edit_modal_event_name" onchange="renderEditStateInputs()" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" data-format="" disabled selected>-- Select an Official Sport --</option>
                <?php 
                mysqli_data_seek($sports_query, 0);
                while($sport = mysqli_fetch_assoc($sports_query)) {
                    echo "<option value='" . htmlspecialchars($sport['sport_name']) . "' data-format='" . $sport['format_type'] . "'>" . $sport['sport_name'] . "</option>";
                }
                ?>
            </select>
            
            <div id="edit-dynamic-state-inputs" style="margin-bottom: 15px; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #eee; display: none;">
            </div>
            
            <label style="font-weight: bold; font-size: 14px;">Venue / Stadium</label>
            <select id="edit_modal_venue" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" disabled selected>-- Select an Official Venue --</option>
                <?php 
                mysqli_data_seek($venues_query, 0);
                while($venue = mysqli_fetch_assoc($venues_query)) {
                    echo "<option value='" . htmlspecialchars($venue['venue_name']) . "'>" . $venue['venue_name'] . "</option>";
                }
                ?>
            </select>

            <label style="font-weight: bold; font-size: 14px;">Match Phase / Level</label>
            <select id="edit_modal_match_phase" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; background-color: white;">
                <option value="" disabled>-- Select Match Phase --</option>
                <?php 
                mysqli_data_seek($phases_query, 0);
                while($phase = mysqli_fetch_assoc($phases_query)) {
                    echo "<option value='" . htmlspecialchars($phase['phase_name']) . "'>" . $phase['phase_name'] . "</option>";
                }
                ?>
            </select>
            
            <label style="font-weight: bold; font-size: 14px;">Event Date</label>
            <input type="date" id="edit_modal_event_date" required style="width: 100%; padding: 10px; margin: 8px 0 25px 0; border: 1px solid #ccc; border-radius: 4px;">
            
            <div class="modal-actions">
                <button type="button" onclick="closeEditEventModal()" class="btn-cancel">Cancel</button>
                <button type="submit" style="background: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Event</button>
            </div>
        </form>
    </div>
</div>

<div id="setResultModal" class="custom-modal">
    <div class="modal-box" style="width: 400px; text-align: left;">
        <h3 class="modal-title" style="margin-bottom: 10px; color: #d4af37; text-align: center;">🏆 Set Match Result</h3>
        
        <form id="setResultForm" onsubmit="submitResult(event)">
            <input type="hidden" id="result_event_id">
            
            <div id="result_event_info" style="text-align: center; margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px; border: 1px solid #ddd; font-weight: bold;"></div>

            <div id="wrapper_medals" style="display: none; background: #fffdf5; padding: 15px; border-radius: 4px; border: 1px solid #ffecb3; margin-bottom: 15px;">
                
                <div id="input_group_gold_silver">
                    <label style="font-weight: bold; font-size: 14px; color: #d4af37;">🥇 Gold Medal Winner</label>
                    <select id="result_gold" style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;"></select>

                    <label style="font-weight: bold; font-size: 14px; color: #9e9e9e;">🥈 Silver Medal Winner</label>
                    <select id="result_silver" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></select>
                </div>

                <div id="input_group_bronze" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e0e0e0;">
                    <label style="font-weight: bold; font-size: 14px; color: #cd7f32;">🥉 Bronze Medal Winner</label>
                    <select id="result_bronze" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"></select>
                </div>
            </div>

            <div id="wrapper_generic" style="display: none; text-align: center; margin-bottom: 20px; color: #666; font-style: italic;">
                This match phase does not award medals.<br>Mark this match as Completed?
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeSetResultModal()" class="btn-cancel">Cancel</button>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Confirm Result</button>
            </div>
        </form>
    </div>
</div>

<div id="athleteProfileModal" class="custom-modal">
    <div class="modal-box" style="width: 450px; padding: 0; overflow: hidden; text-align: left;">
        
        <div style="background: #0056b3; padding: 30px 20px 20px 20px; text-align: center; position: relative;">
            <button onclick="closeProfileModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; color: white; font-size: 20px; cursor: pointer;">✖</button>
            
            <img id="profile_flag" src="" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid white; object-fit: cover; box-shadow: 0 4px 8px rgba(0,0,0,0.2); background: white;">
            <h2 id="profile_name" style="color: white; margin: 15px 0 5px 0; font-size: 22px;"></h2>
            <div id="profile_state_gender" style="color: #e0e0e0; font-size: 14px; font-weight: bold;"></div>
        </div>

        <div style="padding: 20px; background: #f8f9fa;">
            <h4 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #ddd; padding-bottom: 5px;">📅 Registered Events</h4>
            
            <div id="profile_events_list" style="max-height: 250px; overflow-y: auto;">
                </div>
        </div>

    </div>
</div>

<!-- EDIT ATHLETE MODAL -->
<div id="editAthleteModal" class="custom-modal">
    <div class="modal-box" style="width: 400px; text-align: left;">
        <h3 class="modal-title" style="margin-bottom: 20px; color: #0056b3; text-align: center;">✏️ Edit Athlete Details</h3>
        
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="edit_athlete">
            <input type="hidden" name="athlete_id" id="edit_athlete_id">
            
            <label style="font-weight: bold; font-size: 14px;">Full Name</label>
            <input type="text" name="full_name" id="edit_athlete_name" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px;">
            
            <label style="font-weight: bold; font-size: 14px;">Contingent / State</label>
            <select name="contingent_state" id="edit_athlete_state" required style="width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px;">
                <?php foreach(array_keys($all_states) as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>

            <label style="font-weight: bold; font-size: 14px;">Gender Category</label>
            <select name="gender" id="edit_athlete_gender" required style="width: 100%; padding: 10px; margin: 8px 0 25px 0; border: 1px solid #ccc; border-radius: 4px;">
                <option value="Male">Male (Lelaki)</option>
                <option value="Female">Female (Wanita)</option>
                <option value="Mixed">Mixed (Campuran)</option>
            </select>
            
            <div class="modal-actions">
                <button type="button" onclick="closeEditAthleteModal()" class="btn-cancel">Cancel</button>
                <button type="submit" style="background: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Athlete</button>
            </div>
        </form>
    </div>
</div>

<div id="assignAthleteModal" class="custom-modal">
    <div class="modal-box" style="width: 450px; text-align: left;">
        <h3 class="modal-title" style="margin-bottom: 5px; color: #28a745; text-align: center;">📅 Assign to Match</h3>
        <p id="assign_athlete_name_display" style="text-align: center; color: #666; font-weight: bold; margin-bottom: 20px;"></p>
        
        <form id="assignAthleteForm" onsubmit="submitAthleteAssignment(event)">
            <input type="hidden" id="assign_athlete_id">
            
            <label style="font-weight: bold; font-size: 14px;">Available Upcoming Matches</label>
            <select id="assign_event_dropdown" required style="width: 100%; padding: 10px; margin: 8px 0 25px 0; border: 1px solid #ccc; border-radius: 4px;">
                </select>
            
            <div class="modal-actions">
                <button type="button" onclick="closeAssignModal()" class="btn-cancel">Cancel</button>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<div id="viewParticipantsModal" class="custom-modal">
    <div class="modal-box" style="width: 500px; text-align: left;">
        <h3 id="vp_event_title" class="modal-title" style="margin-bottom: 5px; color: #0056b3; text-align: center;">👥 Event Participants</h3>
        <p id="vp_event_phase" style="text-align: center; color: #666; font-size: 13px; margin-bottom: 20px;"></p>
        
        <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 6px; padding: 15px; max-height: 300px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <tbody id="vp_participants_list">
                    </tbody>
            </table>
        </div>
        
        <div class="modal-actions" style="margin-top: 20px;">
            <button type="button" onclick="closeParticipantsModal()" class="btn-cancel" style="width: 100%;">Close List</button>
        </div>
    </div>
</div>

<div id="medalWinnersModal" class="custom-modal">
    <div class="modal-box" style="width: 550px; text-align: left;">
        <h3 id="mw_title" class="modal-title" style="margin-bottom: 5px; color: #333; text-align: center;">🏆 Medal Winners</h3>
        <p id="mw_subtitle" style="text-align: center; color: #666; font-size: 14px; margin-bottom: 20px; font-weight: bold;"></p>
        
        <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 6px; padding: 15px; max-height: 350px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <tbody id="mw_winners_list">
                    </tbody>
            </table>
        </div>
        
        <div class="modal-actions" style="margin-top: 20px;">
            <button type="button" onclick="closeMedalWinnersModal()" class="btn-cancel" style="width: 100%;">Close Panel</button>
        </div>
    </div>
</div>

<div id="systemToast" style="position: fixed; top: 20px; right: -400px; background: #333; color: white; padding: 15px 25px; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); font-weight: bold; display: flex; align-items: center; gap: 12px;">
    <span id="toastIcon" style="font-size: 20px;">ℹ️</span>
    <span id="toastMessage" style="font-size: 14px; letter-spacing: 0.5px;">Notification</span>
</div>

<div id="confirmUnassignModal" class="custom-modal">
    <div class="modal-box" style="width: 400px;">
        <div style="font-size: 40px; margin-bottom: 15px;">⚠️</div>
        <h3 class="modal-title">Remove Athlete?</h3>
        <p id="unassign_confirm_text" class="modal-text" style="margin-bottom: 25px;"></p>
        
        <div class="modal-actions">
            <button onclick="closeUnassignConfirm()" class="btn-cancel">Cancel</button>
            <button id="executeUnassignBtn" class="btn-danger" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Yes, Remove Athlete</button>
        </div>
    </div>
</div>