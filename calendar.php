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
        return renderCalendarHtml();
    }, $content);
    
    $args[0] = $content;
    return $args;
}

/**
 * Generate calendar HTML
 */
function renderCalendarHtml() {
    // Get booked dates from file
    $bookedDates = getBookedDates();
    
    // Get current date
    $currentDate = new DateTime();
    $year = $currentDate->format('Y');
    $month = $currentDate->format('m');
    
    // Generate calendar HTML
    $html = '<div class="calendar-container">';
    $html .= '<div class="calendar-header">';
    $html .= '<h3>Availability Calendar</h3>';
    $html .= '</div>';
    $html .= '<div class="calendar-grid">';
    
    // Generate calendar days
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
    $firstDayOfWeek = date('N', mktime(0, 0, 0, $month, 1, $year));
    
    // Days of week header
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($days as $day) {
        $html .= '<div class="calendar-day-header">' . $day . '</div>';
    }
    
    // Empty cells for days before the first day of the month
    for ($i = 1; $i < $firstDayOfWeek; $i++) {
        $html .= '<div class="calendar-empty"></div>';
    }
    
    // Calendar days
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isBooked = in_array($date, $bookedDates);
        
        if ($isBooked) {
            $html .= '<div class="calendar-day booked">' . $day . '</div>';
        } else {
            $html .= '<div class="calendar-day available">' . $day . '</div>';
        }
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
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
    global $Wcms;
    
    // Only show on calendar settings page if logged in
    if ($Wcms->loggedIn && $Wcms->currentPage == 'calendarSettings') {
        $js = '
        <script>
        function toggleBooking(date) {
            var bookedDates = localStorage.getItem("bookedDates");
            if (!bookedDates) bookedDates = "[]";
            bookedDates = JSON.parse(bookedDates);
            
            var index = bookedDates.indexOf(date);
            if (index > -1) {
                bookedDates.splice(index, 1);
            } else {
                bookedDates.push(date);
            }
            
            localStorage.setItem("bookedDates", JSON.stringify(bookedDates));
            location.reload();
        }
        </script>';
        $args[0] .= $js;
    }
    
    return $args;
}

/**
 * Add CSS for calendar styling
 */
function calendarCss($args) {
    $css = '
    <style>
    .calendar-container {
        border: 1px solid #ccc;
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
        background: #f9f9f9;
    }
    
    .calendar-header {
        text-align: center;
        margin-bottom: 15px;
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
        background: #e0e0e0;
    }
    
    .calendar-day {
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
        background: #fff;
    }
    
    .calendar-day.booked {
        background-color: #ff6b6b;
        color: white;
    }
    
    .calendar-day.available {
        background-color: #51cf66;
        color: white;
    }
    
    .calendar-empty {
        min-height: 40px;
        border: 1px solid #ddd;
        background: #fff;
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