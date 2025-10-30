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
    global $Wcms;
    $args[0] .= "<link rel='stylesheet' href='{$Wcms->url('plugins/calendar/css/style.css')}'>"; 
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
        <div class="settings-flex-container">
            <div class="settings-calendar-section">
                <div class="calendar-section">
                    <h3>Calendar Management</h3>
                    <p class="settings-description">Manage your calendar by selecting dates below. Click on dates to add them to the booked list. When you are done, save the dates.</p>
                    
                    <div class="calendar-controls">
                        <button id="select-all-dates">Occuper tout le mois</button>
                        <button id="clear-dates">Libérer tout le mois</button>
                    </div>
                    
                    <div class="calendar-container settings-calendar" id="settings-calendar"></div>
                </div>
            </div>
            
            <div class="settings-textarea-section">
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
        </div>
    </div>
    <script>
    function toggleClass(divobject, class_name) {
        if (divobject.classList.contains(class_name)) {
            divobject.classList.remove(class_name);
        } else {
            divobject.classList.add(class_name);
        }
    }
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
                    currentDates = currentDates.filter(d => d !== date);
                    toggleClass(e.target, "selected");
                } else {
                    currentDates.push(date);
                    toggleClass(e.target, "selected");
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
