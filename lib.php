<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->libdir . '/accesslib.php');

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
 * Get student attendance percentage
 */
function local_academic_dashboard_get_student_attendance($userid, $courseid) {
    global $DB;
    
    // Check if attendance module exists
    if (!$DB->record_exists('modules', ['name' => 'attendance'])) {
        return null;
    }
    
    // Get all attendance instances for this course
    $sql = "SELECT a.id
            FROM {attendance} a
            WHERE a.course = ?";
    
    $attendances = $DB->get_records_sql($sql, [$courseid]);
    
    if (empty($attendances)) {
        return null;
    }
    
    $totalSessions = 0;
    $presentCount = 0;
    
    foreach ($attendances as $attendance) {
        // Get all sessions for this attendance instance
        $sessions = $DB->get_records('attendance_sessions', ['attendanceid' => $attendance->id]);
        
        if (empty($sessions)) {
            continue;
        }
        
        foreach ($sessions as $session) {
            // Check if session has been taken (sessdate in the past)
            if ($session->sessdate > time()) {
                continue; // Skip future sessions
            }
            
            $totalSessions++;
            
            // Get the student's log for this session
            $log = $DB->get_record('attendance_log', [
                'sessionid' => $session->id,
                'studentid' => $userid
            ]);
            
            if ($log) {
                // Get the status for this log entry
                $status = $DB->get_record('attendance_statuses', ['id' => $log->statusid]);
                
                // Check if status is present (acronym P, L, or similar present statuses)
                if ($status && in_array($status->acronym, ['P', 'L', 'E'])) {
                    $presentCount++;
                }
            }
        }
    }
    
    if ($totalSessions > 0) {
        return round(($presentCount / $totalSessions) * 100);
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
