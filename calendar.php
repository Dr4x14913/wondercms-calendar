<?php
/**
 * Calendar plugin.
 *
 * Displays a calendar with booked dates in red and available dates in green.
 * Booked dates can be managed by admins in the calendarSettings page.
 *
 * @author Your Name
 */

global $Wcms;

// Initialize plugin
if (defined('VERSION')) {
    $Wcms->addListener('page', 'renderCalendar');
    $Wcms->addListener('js', 'calendarJs');
    $Wcms->addListener('css', 'calendarCss');
    $Wcms->addListener('settings', 'calendarSettings');
}

/**
 * Render calendar on pages
 */
function renderCalendar($args) {
    global $Wcms;
    
    // Get current page content
    $content = $args[0];
    
    // Replace all divs with class "customCalendar" with the calendar
    $content = preg_replace_callback('/<div class="customCalendar">.*?<\/div>/s', function($matches) {
        return '<div class="calendar-container"></div>';
    }, $content);
    
    $args[0] = $content;
    return $args;
}

/**
 * Get booked dates from file
 */
function getBookedDates() {
    $file = __DIR__ . '/booked_dates.txt';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return explode("\n", trim($content));
    }
    return [];
}

/**
 * Save booked dates to file
 */
function saveBookedDates($dates) {
    $file = __DIR__ . '/booked_dates.txt';
    file_put_contents($file, implode("\n", $dates));
}

/**
 * Add JavaScript for calendar functionality
 */
function calendarJs($args) {
    // Get all booked dates for JavaScript initialization
    $bookedDates = getBookedDates();
    
    // Convert booked dates to JavaScript array
    $bookedDatesJson = json_encode($bookedDates);
    
    $js = '
    <script>
    // JavaScript for calendar navigation without page reload
    document.addEventListener("DOMContentLoaded", function() {
        // Store booked dates in JavaScript
        var bookedDates = ' . $bookedDatesJson . ';
        
        // Current date
        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth(); // 0-based month
        
        // Calendar display function
        function displayCalendar(year, month) {
            // Get calendar container
            var calendarContainer = document.querySelector(".calendar-container");
            var monthNames = ["janvier", "février", "mars", "avril", "mai", "juin",
                              "juillet", "août", "septembre", "octobre", "novembre", "décembre"];
            
            // Create the first day of the month
            var firstDayOfMonth = new Date(year, month, 1);
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var firstDayOfWeek = firstDayOfMonth.getDay(); // 0 for Sunday, 1 for Monday, etc.
            
            // Adjust for Sunday being 0 in JavaScript but 7 in PHP
            if (firstDayOfWeek === 0) {
                firstDayOfWeek = 7;
            }
            
            // Generate calendar HTML
            var html = \'<div class="calendar-header">\' +
                \'<h3>Calendrier des disponibilité</h3>\' +
                \'<div class="calendar-navigation">\' +
                \'<div class="nav-button nav-button-ajax prev-month">&laquo; Prev</div>\' +
                \'<span class="current-month">\' + monthNames[month] + \' \' + year + \'</span>\' +
                \'<div class="nav-button nav-button-ajax next-month">Next &raquo;</div>\' +
                \'</div>\' +
                \'</div>\' +
                \'<div class="calendar-grid">\';
            
            // Days of week header
            var days = ["lun", "mar", "mer", "jeu", "ven", "sam", "dim"];
            for (var i = 0; i < days.length; i++) {
                html += \'<div class="calendar-day-header">\' + days[i] + \'</div>\';
            }
            
            // Empty cells for days before the first day of the month
            for (var i = 1; i < firstDayOfWeek; i++) {
                html += \'<div class="calendar-empty"></div>\';
            }
            
            // Calendar days
            for (var day = 1; day <= daysInMonth; day++) {
                var date = year + "-" + (month + 1 < 10 ? "0" : "") + (month + 1) + "-" + (day < 10 ? "0" : "") + day;
                var isBooked = bookedDates.includes(date);
                
                if (isBooked) {
                    html += \'<div class="calendar-day booked" date="\'+date+\'">\' + day + \'</div>\';
                } else {
                    html += \'<div class="calendar-day available" date="\'+date+\'">\' + day + \'</div>\';
                }
            }
            
            html += \'</div>\';
            
            // Set the HTML to the container
            calendarContainer.innerHTML = html;
            
            // Reattach event listeners
            attachEventListeners();
        }
        
        // Attach event listeners to navigation buttons
        function attachEventListeners() {
            var navButtons = document.querySelectorAll(".nav-button-ajax");
            navButtons.forEach(function(button) {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (e.target.classList.contains("prev-month")) {
                        currentMonth--;
                        if (currentMonth < 0) {
                            currentMonth = 11;
                            currentYear--;
                        }
                    } else if (e.target.classList.contains("next-month")) {
                        currentMonth++;
                        if (currentMonth > 11) {
                            currentMonth = 0;
                            currentYear++;
                        }
                    }
                    displayCalendar(currentYear, currentMonth);
                });
            });
        }
        
        // Initialize calendar display
        displayCalendar(currentYear, currentMonth);
    });
    </script>';
    
    $args[0] .= $js;
    return $args;
}

/**
 * Add CSS for calendar styling
 */
function calendarCss($args) {
    $css = '
    <style>
    .calendar-container {
        padding: 20px;
        border-radius: 8px;
        margin: 25px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .calendar-header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .calendar-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 15px;
        padding: 0 10px;
    }
    
    .nav-button {
        padding: 5px 10px;
        background: #37474F;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .nav-button:hover {
        background: #636363;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .current-month {
        font-weight: 600;
        font-size: 1.3em;
        flex-grow: 1;
        text-align: center;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-top: 15px;
    }
    
    .calendar-day-header {
        text-align: center;
        font-weight: 600;
        padding: 12px;
        background: #6b5a44;
        color: #fff;
        border-radius: 6px;
        font-size: 0.9em;
    }
    
    .calendar-day {
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        color: #333;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .calendar-day.booked {
        background-color: #ff6b6b;
        color: white;
        border-color: #ff5252;
    }
    
    .calendar-day.available {
        background-color: #98b51b;
        color: white;
        border-color: #8bc34a;
    }
    
    .calendar-day.selected::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.3);
        pointer-events: none;
    }
    
    .calendar-empty {
        min-height: 50px;
        border: 1px solid #e0e0e0;
    }
    
    .calendar-day:hover {
        opacity: 0.9;
        transform: scale(1.05);
        z-index: 1;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .calendar-settings-content {
        margin-top: 20px;
        background: #ffffff;
        padding: 30px;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        max-width: 800px;
        margin: 25px auto;
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .calendar-settings-content textarea {
        color: #333 !important;
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        resize: vertical;
        font-family: monospace;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }

    .calendar-settings-content textarea:focus {
        outline: none;
        border-color: #4a90e2;
        box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
    }

    
    #calendar-settings-toggle {
        cursor: pointer;
        transition: all 0.3s ease;
        position: fixed;
        top: 141px;
        right: 49px;
        z-index: 1000;
    }
    
    #calendar-settings-toggle:hover {
        background: #357abd;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .settings-title {
        font-size: 1.6em;
        font-weight: 700;
        color: #333;
        margin: 0;
    }
    
    .settings-description {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
        font-size: 1.05em;
    }
    
    .calendar-controls {
        display: flex;
        gap: 12px;
        margin-bottom: 10;
        justify-content: center;
    }
    
    .calendar-controls button {
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .calendar-controls button:hover {
        background: #636363;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .calendar-input-group {
        margin-bottom: 25px;
    }
    
    .calendar-input-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
        font-size: 1.1em;
    }
    
    .calendar-input-group textarea {
        width: 100%;
        min-height: 150px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: monospace;
        font-size: 14px;
        resize: vertical;
    }
    
    .calendar-button {
        padding: 12px 25px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 1.1em;
        align-self: flex-start;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .calendar-button:hover {
        background: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .calendar-button:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .calendar-section {
        margin-bottom: 35px;
    }
    
    .calendar-section h3 {
        margin-bottom: 15px;
        color: #333;
        border-bottom: 3px solid #4a90e2;
        padding-bottom: 8px;
        font-size: 1.3em;
    }
    
    .calendar-section p {
        margin-bottom: 15px;
        color: #666;
        line-height: 1.6;
    }
    
    .calendar-section .calendar-container {
        margin: 0;
        background: #fff;
        box-shadow: none;
        border: 1px solid #e0e0e0;
    }
    
    .calendar-section .calendar-container .calendar-grid {
        gap: 3px;
    }
    
    .calendar-section .calendar-container .calendar-day-header {
        font-size: 0.95em;
    }
    
    .calendar-section .calendar-container .calendar-day {
        min-height: 40px;
    }
    
    @media (max-width: 768px) {
        .calendar-grid {
            grid-template-columns: repeat(7, 1fr);
        }
        
        .calendar-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .calendar-controls button {
            width: 100%;
        }
        
        .calendar-navigation {
            flex-direction: column;
            align-items: center;
        }
        
        .current-month {
            margin: 10px 0;
        }
        
        .calendar-settings-content {
            padding: 20px;
            margin: 15px auto;
        }
        
        #calendar-settings-toggle {
            align-self: flex-start;
            margin-left: 0;
        }
    }
    </style>';
    
    $args[0] .= $css;
    return $args;
}

/**
 * Calendar settings page
 */
function calendarSettings($args) {
    global $Wcms;
    
    // Only show settings for logged-in admins
    //if (!$Wcms->loggedIn || $Wcms->currentPage != "calendarsettings" ) 
    if (!$Wcms->loggedIn) {
        return $args;
    }
    
    // Handle form submission
    if (isset($_POST['save_booked_dates'])) {
        $bookedDates = explode(',', $_POST['booked_dates']);
        $bookedDates = array_map('trim', $bookedDates);
        saveBookedDates($bookedDates);
    }
    
    // Get current booked dates
    $bookedDates = getBookedDates();
    
    $settingsHtml = '
    <button id="calendar-settings-toggle" class="btn btn-secondary">Show Calendar Settings</button>
    <div id="calendar-settings-content" class="calendar-settings-content" style="display: none;">
        <div class="calendar-section">
            <h3>Calendar Management</h3>
            <p class="settings-description">Manage your calendar by selecting dates below. Click on dates to add them to the booked list. When you are done, save the dates.</p>
            
            <div class="calendar-controls">
                <button id="select-all-dates">Occuper tout le mois</button>
                <button id="clear-dates">Libérer tout le mois</button>
            </div>
            
            <div class="calendar-container settings-calendar" id="settings-calendar"></div>
        </div>
        
        <div class="calendar-section">
            <h3>Booked Dates Configuration</h3>
            <p class="settings-description">Edit the list of booked dates in comma-separated format (YYYY-MM-DD). Changes will be saved when you click "Save Booked Dates".</p>
            
            <form method="post" class="calendar-form">
                <div class="calendar-input-group">
                    <label for="booked_dates">Booked Dates (comma separated, format: YYYY-MM-DD):</label>
                    <textarea id="booked_dates" name="booked_dates" rows="8">' . implode(',', $bookedDates) . '</textarea>
                </div>
                <input type="submit" name="save_booked_dates" value="Save Booked Dates" class="calendar-button">
            </form>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var toggleButton = document.getElementById("calendar-settings-toggle");
        var settingsContent = document.getElementById("calendar-settings-content");
        
        toggleButton.addEventListener("click", function() {
            if (settingsContent.style.display === "none") {
                settingsContent.style.display = "flex";
                toggleButton.textContent = "Hide Calendar Settings";
            } else {
                settingsContent.style.display = "none";
                toggleButton.textContent = "Show Calendar Settings";
            }
        });

        const settings_calendar = document.getElementById("settings-calendar");
        settings_calendar.addEventListener("click", function(e) {
            var textarea = document.getElementById("booked_dates");
            if (e.target.classList.contains("calendar-day")) {
                var date = e.target.getAttribute("date");
                var currentDates = textarea.value.split(",").map(d => d.trim()).filter(d => d);
                if (currentDates.includes(date)) {
                    // Remove date if it exists
                    currentDates = currentDates.filter(d => d !== date);
                    e.target.classList.remove("selected");
                } else {
                    currentDates.push(date);
                    e.target.classList.add("selected");
                }
                textarea.value = currentDates.join(",");
            }
        });
        
        // Add event listeners for the control buttons
        document.getElementById("select-all-dates").addEventListener("click", function() {
            var calendarDays = document.querySelectorAll(".calendar-day");
            var textarea = document.getElementById("booked_dates");
            var currentDates = textarea.value.split(",").map(d => d.trim()).filter(d => d);
            
            calendarDays.forEach(function(day) {
                var date = day.getAttribute("date");
                if (!currentDates.includes(date)) {
                    currentDates.push(date);
                    day.classList.add("selected");
                }
            });
            
            textarea.value = currentDates.join(",");
        });
        
        document.getElementById("clear-dates").addEventListener("click", function() {
            var calendarDays = document.querySelectorAll(".calendar-day");
            var textarea = document.getElementById("booked_dates");
            var currentDates = textarea.value.split(",").map(d => d.trim()).filter(d => d);
            
            calendarDays.forEach(function(day) {
                var date = day.getAttribute("date");
                currentDates = currentDates.filter(d => d !== date);
                day.classList.add("selected");
            });
            
            textarea.value = currentDates.join(",");
        });
    });
    </script>';
    
    $args[0] .= $settingsHtml;
    return $args;
}
