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
            var monthNames = ["January", "February", "March", "April", "May", "June",
                              "July", "August", "September", "October", "November", "December"];
            
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
                \'<h3>Availability Calendar</h3>\' +
                \'<div class="calendar-navigation">\' +
                \'<a href="#" class="nav-button nav-button-ajax prev-month">&laquo; Prev</a>\' +
                \'<span class="current-month">\' + monthNames[month] + \' \' + year + \'</span>\' +
                \'<a href="#" class="nav-button nav-button-ajax next-month">Next &raquo;</a>\' +
                \'</div>\' +
                \'</div>\' +
                \'<div class="calendar-grid">\';
            
            // Days of week header
            var days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
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
                    html += \'<div class="calendar-day booked">\' + day + \'</div>\';
                } else {
                    html += \'<div class="calendar-day available">\' + day + \'</div>\';
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
        border: 1px solid #37474F;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
        background: #37474F;
        color: #fff;
    }
    
    .calendar-header {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .calendar-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .nav-button {
        padding: 5px 10px;
        background: #37474F;
        color: #fff;
        text-decoration: none;
        border-radius: 3px;
        border: 1px solid #fff;
    }
    
    .nav-button:hover {
        background: #636363;
    }
    
    .current-month {
        font-weight: bold;
        font-size: 1.2em;
        color: #fff;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-top: 10px;
    }
    
    .calendar-day-header {
        text-align: center;
        font-weight: bold;
        padding: 8px;
        background: #636363;
        color: #fff;
    }
    
    .calendar-day {
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #fff;
        background: #37474F;
        color: #fff;
    }
    
    .calendar-day.booked {
        background-color: #ff6b6b;
        color: white;
    }
    
    .calendar-day.available {
        background-color: #98b51b;
        color: white;
    }
    
    .calendar-empty {
        min-height: 40px;
        border: 1px solid #fff;
        background: #37474F;
    }
    
    .calendar-day:hover {
        opacity: 0.8;
        cursor: pointer;
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
    //if (!$Wcms->loggedIn || $Wcms->currentPage != "calendarsettings" ) {
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
    <div class="calendar-settings">
        <h3>Calendar Settings</h3>
        <form method="post">
            <label for="booked_dates">Booked Dates (comma separated, format: YYYY-MM-DD):</label><br><br>
            <textarea id="booked_dates" name="booked_dates" rows="10" cols="50">' . implode(',', $bookedDates) . '</textarea><br><br>
            <input type="submit" name="save_booked_dates" value="Save Booked Dates">
        </form>
    </div>';
    
    $args[0] .= $settingsHtml;
    return $args;
}
