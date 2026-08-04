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