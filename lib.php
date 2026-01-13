<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->libdir . '/accesslib.php');

/**
 * Check if course has Zoom meeting activity
 */
function local_academic_dashboard_course_has_zoom($courseid) {
    global $DB;
    
    // Check if zoom module exists
    if (!$DB->record_exists('modules', ['name' => 'zoom'])) {
        return false;
    }
    
    // Check if course has any zoom activities
    $sql = "SELECT COUNT(*) as count
            FROM {zoom} z
            WHERE z.course = ?";
    
    $result = $DB->get_field_sql($sql, [$courseid]);
    
    return $result > 0;
}

/**
 * Get course statistics
 */
function local_academic_dashboard_get_course_stats($courseid) {
    global $DB;
    
    $stats = new stdClass();
    
    $sql = "SELECT COUNT(DISTINCT ue.userid) as total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {user} u ON u.id = ue.userid
            WHERE e.courseid = ? AND u.deleted = 0 AND ue.status = 0";
    
    $result = $DB->get_record_sql($sql, [$courseid]);
    $stats->students = $result ? $result->total : 0;
    
    // Count resources
    $stats->resources = $DB->count_records('resource', ['course' => $courseid]);
    
    // Count quizzes
    $stats->quizzes = $DB->count_records('quiz', ['course' => $courseid]);
    
    // Count assignments
    $stats->assignments = $DB->count_records('assign', ['course' => $courseid]);
    
    // Count overdue (assignments + quizzes with due date passed)
    $now = time();
    $overdue = 0;
    
    $overdueassignments = $DB->count_records_select('assign', 
        'course = ? AND duedate > 0 AND duedate < ?', 
        [$courseid, $now]);
    $overdue += $overdueassignments;
    
    $overduequizzes = $DB->count_records_select('quiz', 
        'course = ? AND timeclose > 0 AND timeclose < ?', 
        [$courseid, $now]);
    $overdue += $overduequizzes;
    
    $stats->overdue = $overdue;
    
    // Count remaining (assignments + quizzes with future due date)
    $remaining = 0;
    
    $remainingassignments = $DB->count_records_select('assign', 
        'course = ? AND duedate > 0 AND duedate >= ?', 
        [$courseid, $now]);
    $remaining += $remainingassignments;
    
    $remainingquizzes = $DB->count_records_select('quiz', 
        'course = ? AND timeclose > 0 AND timeclose >= ?', 
        [$courseid, $now]);
    $remaining += $remainingquizzes;
    
    $stats->remaining = $remaining;
    
    return $stats;
}

/**
 * Get student progress in course
 */
function local_academic_dashboard_get_student_progress($userid, $courseid) {
    $course = get_course($courseid);
    $completion = new completion_info($course);
    
    if (!$completion->is_enabled()) {
        return null;
    }
    
    $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid);
    
    return $percentage;
}

/**
 * Get student attendance percentage based on Zoom meeting participation
 * Updated to use mdl_zoom_meeting_participants table for attendance data
 */
function local_academic_dashboard_get_student_attendance($userid, $courseid) {
    global $DB;
    
    // Check if zoom module exists
    if (!$DB->record_exists('modules', ['name' => 'zoom'])) {
        return null;
    }
    
    // Get user email for matching in zoom participants
    $user = $DB->get_record('user', ['id' => $userid], 'email, firstname, lastname');
    if (!$user) {
        return null;
    }
    
    // Get all zoom meeting details for this course that have already occurred
    $sql = "SELECT zmd.id as detailsid, z.id as zoomid
            FROM {zoom} z
            JOIN {zoom_meeting_details} zmd ON zmd.zoomid = z.id
            WHERE z.course = ? AND z.start_time < ?";
    
    $meetingdetails = $DB->get_records_sql($sql, [$courseid, time()]);
    
    if (empty($meetingdetails)) {
        return null;
    }
    
    $totalMeetings = count($meetingdetails);
    $attendedCount = 0;
    
    $fullname = trim($user->firstname . ' ' . $user->lastname);
    
    foreach ($meetingdetails as $detail) {
        $sql = "SELECT COUNT(*) as cnt
                FROM {zoom_meeting_participants} zmp
                WHERE zmp.detailsid = ?
                AND (zmp.userid = ? OR zmp.user_email = ? OR zmp.name LIKE ? OR zmp.name LIKE ?)";
        
        $participated = $DB->get_field_sql($sql, [
            $detail->detailsid,
            $userid,
            $user->email,
            '%' . $user->email . '%',
            '%' . $fullname . '%'
        ]);
        
        if ($participated > 0) {
            $attendedCount++;
        }
    }
    
    if ($totalMeetings > 0) {
        return round(($attendedCount / $totalMeetings) * 100);
    }
    
    return null;
}

/**
 * Check if user is a teacher
 */
function local_academic_dashboard_is_teacher($userid) {
    global $DB;
    
    // Get first course where user is enrolled
    $sql = "SELECT DISTINCT c.id
            FROM {course} c
            JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            JOIN {role_assignments} ra ON ra.contextid = ctx.id
            WHERE ra.userid = ? AND c.id > 1
            LIMIT 1";
    
    $course = $DB->get_record_sql($sql, [$userid]);
    
    if (!$course) {
        return false;
    }
    
    $context = context_course::instance($course->id);
    return has_capability('moodle/course:update', $context, $userid);
}

/**
 * Render email modal
 */
function local_academic_dashboard_render_email_modal($recipients = [], $courseid = null) {
    global $OUTPUT;
    
    $html = '<div class="modal fade" id="emailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . get_string('send_email', 'local_academic_dashboard') . '</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="emailForm" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>' . get_string('email_to', 'local_academic_dashboard') . '</label>
                            <input type="text" class="form-control" name="to" value="' . implode(', ', $recipients) . '" readonly>
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_cc', 'local_academic_dashboard') . '</label>
                            <select class="form-control" name="cc[]" multiple>';
    
    if ($courseid) {
        $context = context_course::instance($courseid);
        $users = get_enrolled_users($context);
        
        foreach ($users as $user) {
            $html .= '<option value="' . $user->id . '">' . fullname($user) . ' (' . $user->email . ')</option>';
        }
    }
    
    $html .= '          </select>
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_subject', 'local_academic_dashboard') . '</label>
                            <input type="text" class="form-control" name="subject" value="' . get_string('email_default_subject', 'local_academic_dashboard') . '">
                        </div>
                        <div class="form-group">
                            <label>' . get_string('email_content', 'local_academic_dashboard') . '</label>
                            <textarea class="form-control" name="content" rows="8"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">' . get_string('cancel') . '</button>
                        <button type="submit" class="btn btn-primary">' . get_string('email_send', 'local_academic_dashboard') . '</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Open email composer window
 */
function local_academic_dashboard_open_email_composer($to = '', $courseid = 0) {
    $url = new moodle_url('/local/academic_dashboard/mail_compose.php', [
        'to' => $to,
        'courseid' => $courseid
    ]);
    return $url->out();
}
